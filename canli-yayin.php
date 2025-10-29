<?php
/**
 * Çorlu FM Radio Player
 * 
 * Copyright (c) 2024 Çorlu FM
 * Design & Development by The Network
 * 
 * Bu kod özel mülkiyettir ve korunmaktadır.
 * Yetkisiz kullanım, kopyalama, değiştirme veya dağıtım yasaktır.
 * 
 * @author The Network
 * @copyright 2024 Çorlu FM
 * @license Proprietary - All Rights Reserved
 */

// PHP Backend - XML verisi ve albüm kapağı çekme

// Türkçe karakterleri destekleyen title case fonksiyonu
function toTitleCase($str) {
    if (empty($str)) return '';
    
    // Küçük harflerle başla
    $str = mb_strtolower($str, 'UTF-8');
    $words = preg_split('/\s+/', $str);
    $result = [];
    
    // Küçük kalması gereken bağlaçlar
    $conjunctions = ['ve', 'ile', 'ya', 'ya da', 'de', 'da', 'ki', 'mi', 'mu', 'mü'];
    
    foreach ($words as $index => $word) {
        // İlk kelime her zaman büyük
        if ($index === 0) {
            $result[] = mb_ucfirst($word, 'UTF-8');
        }
        // Bağlaçlar küçük kalmalı
        elseif (in_array($word, $conjunctions)) {
            $result[] = $word;
        }
        // Kısa büyük harfli kelimeleri koru (FM gibi)
        elseif (mb_strlen($word) <= 4 && $word === mb_strtoupper($word)) {
            $result[] = $word;
        } else {
            $result[] = mb_ucfirst($word, 'UTF-8');
        }
    }
    
    return implode(' ', $result);
}

function mb_ucfirst($string, $encoding = null) {
    if (null === $encoding) $encoding = mb_internal_encoding();
    $firstChar = mb_substr($string, 0, 1, $encoding);
    $rest = mb_substr($string, 1, null, $encoding);
    return mb_strtoupper($firstChar, $encoding) . $rest;
}

if (isset($_GET['action']) && $_GET['action'] === 'getStationData') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    try {
        $xmlUrl = 'https://sssx.radyosfer.com/corlufm/stats?sid=1';
        $xmlData = @simplexml_load_file($xmlUrl);
        
        if ($xmlData === false) {
            throw new Exception('XML verisi yüklenemedi');
        }
        
        $rawSong = (string)$xmlData->SONGTITLE;
        $serverTitle = (string)$xmlData->SERVERTITLE;
        $listeners = (int)$xmlData->CURRENTLISTENERS;
        $peakListeners = (int)$xmlData->PEAKLISTENERS;

        // HTML entities'i decode et
        $rawSong = html_entity_decode($rawSong, ENT_QUOTES, 'UTF-8');
        $serverTitle = html_entity_decode($serverTitle, ENT_QUOTES, 'UTF-8');

        $artist = $serverTitle;
        $title = $rawSong;

        // Artist ve title'ı parse et (birden fazla delimiter deneyelim)
        // " - " veya " – " veya " — " veya " | " 
        $delimiters = [' - ', ' – ', ' — ', ' | ', ' / '];
        $parsed = false;
        
        foreach ($delimiters as $delimiter) {
            if (strpos($rawSong, $delimiter) !== false) {
                $parts = explode($delimiter, $rawSong, 2);
                $artist = trim($parts[0]);
                $title = trim($parts[1]);
                $parsed = true;
                break;
            }
        }
        
        // Parse edilemediyse rawSong'i title olarak kullan
        if (!$parsed && !empty($rawSong)) {
            $title = trim($rawSong);
        }
        
        // Eğer şarkı "Çorlu" içeriyorsa, varsayılan logo kullan
        $isDefaultArtwork = false;
        
        // REKLAM TESPİTİ: Sondaki parantez içinde sayıları temizle
        // Örnek: "PASA KIZI RESTURANT (6141)" → "PASA KIZI RESTURANT"
        $title = preg_replace('/\s*\([0-9]+\)$/', '', $title);
        $artist = preg_replace('/\s*\([0-9]+\)$/', '', $artist);
        
        // Artist "Çorlu FM" ise değiştir
        $isCorluFMArtist = false;
        if (stripos($artist, 'corlu fm') !== false || stripos($artist, 'çorlu fm') !== false ||
            $artist === 'ÇORLU FM' || $artist === 'Çorlu FM') {
            $artist = 'Çorlu FM';
            $isCorluFMArtist = true;
            $isDefaultArtwork = true;
        }
        
        // Title'dan "CORLU FM" kelimelerini temizle (sadece gereksiz tekrarları kaldır)
        // "29 EKIM BAYRAMIMIZ KUTLU OLSUN CORLU FM" → "29 Ekim Bayramimiz Kutlu Olsun"
        $title = preg_replace('/\bCORLU FM\b/i', '', $title);
        $title = preg_replace('/\bÇORLU FM\b/i', '', $title);
        $title = trim($title);
        
        // Eğer artist Çorlu FM ise ve title boşsa veya sadece "Çorlu FM" ise
        if ($isCorluFMArtist && (empty($title) || strtolower($title) === 'çorlu fm')) {
            $title = 'Çorlu FM';
            $isDefaultArtwork = true;
        }
        
        if (stripos($rawSong, 'corlu') !== false || stripos($rawSong, 'çorlu') !== false) {
            $isDefaultArtwork = true;
        }
        
        // Title case uygula (Türkçe karakterler için)
        if (!$isDefaultArtwork) {
            $title = toTitleCase($title);
            $artist = toTitleCase($artist);
        }
        
        // iTunes API'den albüm kapağı al
        $artworkUrl = null;
        if ($isDefaultArtwork) {
            // Çorlu FM reklamı/haberlerinde varsayılan logo kullan
            $artworkUrl = 'https://kolaypanel.s3.eu-central-1.amazonaws.com/hello/uploads/2025/10/29082743/corlu-fm-logo-2.webp';
        } else if (!empty($title) && $title !== 'CORLU FM' && $artist !== 'CORLU FM' && $artist !== $title) {
            try {
                // Önce artist + title ile ara (doğru eşleşme)
                $itunesQuery = urlencode($artist . ' ' . $title);
                $itunesUrl = "https://itunes.apple.com/search?term={$itunesQuery}&media=music&limit=10";
                $itunesData = @file_get_contents($itunesUrl);
                
                if ($itunesData) {
                    $itunesJson = json_decode($itunesData, true);
                    if (!empty($itunesJson['results'])) {
                        // Artist name eşleşmesi kontrolü
                        foreach ($itunesJson['results'] as $result) {
                            $resultArtist = strtolower($result['artistName'] ?? '');
                            $searchArtist = strtolower($artist);
                            
                            // Artist name fuzzy match
                            if (strpos($resultArtist, $searchArtist) !== false || strpos($searchArtist, $resultArtist) !== false) {
                                $artworkUrl = str_replace('100x100bb.jpg', '512x512bb.jpg', $result['artworkUrl100']);
                                break;
                            }
                        }
                        
                        // Eşleşme bulunamadıysa ilk sonucu al
                        if (!$artworkUrl && !empty($itunesJson['results'][0]['artworkUrl100'])) {
                            $artworkUrl = str_replace('100x100bb.jpg', '512x512bb.jpg', $itunesJson['results'][0]['artworkUrl100']);
                        }
                    }
                }
                
                // Artist eşleşmesi bulunamadıysa sadece title ile dene
                if (!$artworkUrl && !empty($title)) {
                    $itunesQuery = urlencode($title);
                    $itunesUrl = "https://itunes.apple.com/search?term={$itunesQuery}&media=music&limit=1";
                    $itunesData = @file_get_contents($itunesUrl);
                    if ($itunesData) {
                        $itunesJson = json_decode($itunesData, true);
                        if (!empty($itunesJson['results']) && !empty($itunesJson['results'][0]['artworkUrl100'])) {
                            $artworkUrl = str_replace('100x100bb.jpg', '512x512bb.jpg', $itunesJson['results'][0]['artworkUrl100']);
                        }
                    }
                }
            } catch (Exception $e) {
                // iTunes API hatası, devam et
            }
        }
        
        $data = [
            'title' => $title,
            'artist' => $artist,
            'listeners' => $listeners,
            'peakListeners' => $peakListeners,
            'status' => 'Canlı Yayın',
            'timestamp' => time(),
            'artworkUrl' => $artworkUrl
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'data' => [
                'title' => 'Canlı Yayın',
                'artist' => 'Çorlu FM',
                'listeners' => 0,
                'peakListeners' => 0,
                'status' => 'Canlı Yayın',
                'artworkUrl' => null
            ]
        ]);
    }
    
    exit;
}

// Son çalan şarkıları çek
if (isset($_GET['action']) && $_GET['action'] === 'getRecentlyPlayed') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    try {
        $playedUrl = 'https://sssx.radyosfer.com/corlufm/played?sid=1';
        $htmlContent = @file_get_contents($playedUrl);
        
        if ($htmlContent === false) {
            throw new Exception('Son çalan şarkılar yüklenemedi');
        }
        
        // HTML parser ile çek
        $tracks = [];
        
        // DOMDocument kullan
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Tablo satırlarını al (header satırları + Current Song'dan sonra gelen)
        $rows = $xpath->query("//table//tr[position()>2]");
        
        foreach ($rows as $rowIndex => $row) {
            $cells = $xpath->query("./td", $row);
            
            if ($cells->length >= 2) {
                // İkinci kolonda şarkı adı var
                $songTitle = trim(strip_tags($cells->item(1)->textContent));
                
                // "Current Song" içeren satırı atla (3. kolon)
                if ($cells->length >= 3) {
                    $thirdCell = trim(strip_tags($cells->item(2)->textContent));
                    if (stripos($thirdCell, 'Current Song') !== false) {
                        continue;
                    }
                }
                
                // Boş olanları atla
                if (empty($songTitle)) {
                    continue;
                }
                
                // "CORLU FM" içerenleri filtrele
                if (stripos($songTitle, 'CORLU FM') !== false || 
                    stripos($songTitle, 'ÇORLU FM') !== false ||
                    stripos($songTitle, 'Çorlu') !== false ||
                    stripos($songTitle, 'ÇORLU') !== false) {
                    continue;
                }
                
                // "Saat_" ile başlayanları filtrele
                if (stripos($songTitle, 'Saat_') === 0) {
                    continue;
                }
                
                // Parantez içinde sadece sayı olanları filtrele (reklam ID'leri)
                // Örnek: "ASDASD (6180)" → Atla
                if (preg_match('/\s*\([0-9]+\)$/', $songTitle)) {
                    continue;
                }
                
                // Mesaj metinlerini filtrele (KUTLU OLSUN dahil tüm mesajlar son çalanda görünmez)
                $mesajListesi = [
                    'Geceler Diler', 'Sabahlar Diler', 'Günler Diler',
                    'Iyi Geceler', 'İyi Geceler', 'İyi Sabahlar', 'İyi Günler',
                    'Iyi Geceler Diler', 'Iyi Sabahlar Diler', 'Iyi Günler Diler',
                    'Iyı Geceler Diler', 'İyi Sabahlar Diler', 'İyi Günler Diler',
                    'Kutlu Olsun', 'KUTLU OLSUN', 'Kutlu Olsun!',
                    'Good Morning', 'Good Night', 'Have A Nice Day'
                ];
                
                $isMesaj = false;
                foreach ($mesajListesi as $mesaj) {
                    if (stripos($songTitle, $mesaj) !== false) {
                        $isMesaj = true;
                        break;
                    }
                }
                if ($isMesaj) {
                    continue;
                }
                
                // Artist ve title'ı parse et
                $delimiters = [' - ', ' – ', ' — ', ' | ', ' / '];
                $parsed = false;
                $artist = '';
                $title = '';
                
                foreach ($delimiters as $delimiter) {
                    if (strpos($songTitle, $delimiter) !== false) {
                        $parts = explode($delimiter, $songTitle, 2);
                        $artist = trim($parts[0]);
                        $title = trim($parts[1]);
                        $parsed = true;
                        break;
                    }
                }
                
                // Delimiter yoksa atla (geçersiz format)
                if (!$parsed) {
                    continue;
                }
                
                // REKLAM TESPİTİ: Parantez içindeki sayıları temizle
                $title = preg_replace('/\s*\([0-9]+\)$/', '', $title);
                $artist = preg_replace('/\s*\([0-9]+\)$/', '', $artist);
                
                // Title case uygula
                $title = toTitleCase($title);
                $artist = toTitleCase($artist);
                
                // Artist "Çorlu FM" ise listeleme
                if (stripos($artist, 'corlu fm') !== false || 
                    stripos($artist, 'çorlu fm') !== false ||
                    stripos($artist, 'corlu') !== false ||
                    $artist === 'Çorlu FM') {
                    continue;
                }
                
                // Sadece title varsa ekle (artist yoksa boş kalmalı)
                if (!empty($title) && $title !== 'CORLU FM' && $title !== 'ÇORLU FM') {
                    $tracks[] = [
                        'title' => $title,
                        'artist' => $artist
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $tracks
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'data' => []
        ]);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#121212">
    <title>Çorlu FM · Canlı Radyo Player</title>
    
    <!-- Performance optimizations -->
    <link rel="preconnect" href="https://kolaypanel.s3.eu-central-1.amazonaws.com">
    <link rel="dns-prefetch" href="https://sssx.radyosfer.com">
    <link rel="dns-prefetch" href="https://itunes.apple.com">
    
    <link rel="icon" type="image/webp" href="https://kolaypanel.s3.eu-central-1.amazonaws.com/hello/uploads/2025/10/29082743/corlu-fm-logo-2.webp">
    
    <!-- System fonts - no external font loading -->
    <style>
        :root {
            color-scheme: dark;
            --bg-primary: #121212;
            --bg-secondary: #181818;
            --accent: #1DB954;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --border-radius: 16px;
        }

        :root.light-mode {
            color-scheme: light;
            --bg-primary: #f5f5f5;
            --bg-secondary: #ffffff;
            --accent: #1DB954;
            --text-primary: #212121;
            --text-secondary: #666666;
        }

        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: radial-gradient(circle at top, #1d1d1d, #090909 65%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 24px;
            transition: background 0.3s ease;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body.light-mode {
            background: radial-gradient(circle at top, #f0f0f0, #e5e5e5 65%);
        }

        .cover-art .video-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: inherit;
            overflow: hidden;
            opacity: 0;
            transition: opacity 1s ease;
            z-index: 0;
        }

        .cover-art .video-background.visible {
            opacity: 1;
        }

        .cover-art .video-background iframe {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100vw;
            height: 56.25vw;
            min-width: 100%;
            min-height: 177.78%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            border-radius: inherit;
        }

        .cover-art:has(.video-background.visible) {
            background-image: none !important;
        }

        body.light-mode .cover-art .video-background.visible {
            opacity: 0.9;
        }

        .player {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 24px;
            width: min(100%, 960px);
            padding: 24px;
            border-radius: var(--border-radius);
            background: linear-gradient(135deg, rgba(24,24,24,0.92), rgba(12,12,12,0.95));
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255,255,255,0.05);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            transition: transform 0.4s ease, background 0.3s ease;
            overflow: hidden;
            content-visibility: auto;
            contain-intrinsic-size: 600px;
        }

        /* Video yüklüyse daha fazla blur */
        .player:has(.cover-art .video-background.visible) {
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
        }

        .player::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.3), rgba(0,0,0,0.5));
            pointer-events: none;
            z-index: 0;
        }

        .player > * {
            position: relative;
            z-index: 1;
        }

        body.light-mode .player {
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(250,250,250,0.92));
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0,0,0,0.05);
        }

        body.light-mode .player::before {
            background: linear-gradient(135deg, rgba(255,255,255,0.5), rgba(255,255,255,0.7));
        }

        .player:hover {
            transform: translateY(-4px);
        }

        .theme-toggle {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
            -webkit-tap-highlight-color: transparent;
        }

        .theme-toggle:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }

        body.light-mode .theme-toggle {
            border: 1px solid rgba(0,0,0,0.15);
            background: rgba(0,0,0,0.05);
        }

        body.light-mode .theme-toggle:hover {
            background: rgba(0,0,0,0.1);
        }

        .layout {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .cover-art {
            flex-shrink: 0;
            width: clamp(200px, 25vw, 320px);
            height: clamp(200px, 25vw, 320px);
            min-height: 200px;
            aspect-ratio: 1;
            border-radius: var(--border-radius);
            background-color: #2a2a2a;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }


        body.light-mode .cover-art {
            background-color: #f0f0f0;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
        }

        .cover-art.is-logo {
            background-size: cover !important;
            background-color: transparent !important;
        }

        .cover-art.is-logo::before {
            display: none !important;
        }

        .cover-art::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(29,185,84,0.1), transparent);
            pointer-events: none;
            z-index: 1;
            transition: opacity 0.5s ease;
        }

        /* Video yüklenince overlay gizle */
        .cover-art:has(.video-background.visible)::before {
            opacity: 0 !important;
        }
        
        .cover-art:has(.video-background.visible) {
            box-shadow: none !important;
        }

        .track-meta {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 8px;
            background: rgba(29,185,84,0.15);
            border: 1px solid rgba(29,185,84,0.3);
            border-radius: 24px;
            font-size: 14px;
            font-weight: 500;
            color: var(--accent);
            width: fit-content;
        }

        .badge.on-air {
            display: block;
            background: #000000;
            font-size: 18px;
            font-weight: 900;
            color: var(--text-secondary);
            text-transform: uppercase;
            width: fit-content;
            border: 6px solid #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 0 2px rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }

        body.light-mode .badge.on-air {
            background: #ffffff;
            border: 6px solid #000000;
            color: #000000;
        }

        .badge.on-air.playing {
            color: #ff0000;
        }

        body.light-mode .badge.on-air.playing {
            color: #ff0000;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: var(--text-secondary);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .live-indicator.playing .pulse-dot {
            background: #ff4444;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }

        .track-meta h1 {
            font-size: clamp(24px, 4vw, 42px);
            line-height: 1.05;
            font-weight: 700;
            margin: 0;
            min-height: 44px;
            background: linear-gradient(135deg, #ffffff, #b3b3b3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        body.light-mode .track-meta h1 {
            background: none;
            -webkit-background-clip: unset;
            -webkit-text-fill-color: unset;
            color: var(--text-primary);
        }

        .track-meta h2 {
            font-size: clamp(16px, 2.5vw, 24px);
            font-weight: 600;
            color: var(--text-secondary);
            margin: 0;
            min-height: 28px;
        }

        .controls {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .play-toggle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            background: var(--accent);
            color: white;
            font-size: 28px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(29,185,84,0.3);
            -webkit-tap-highlight-color: transparent;
        }

        .play-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 32px rgba(29,185,84,0.4);
        }

        .play-toggle:active {
            transform: scale(0.95);
        }

        .secondary {
            padding: 12px 24px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 24px;
            background: transparent;
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-tap-highlight-color: transparent;
        }

        body.light-mode .secondary {
            border: 1px solid rgba(0,0,0,0.2);
        }

        .secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
        }

        body.light-mode .secondary:hover {
            background: rgba(0,0,0,0.05);
            border-color: rgba(0,0,0,0.3);
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
            margin-left: auto;
        }

        .live-indicator.playing {
            color: #ff4444;
            font-weight: bold;
        }

        .volume-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
        }

        .volume-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.2);
            background: transparent;
            color: var(--text-primary);
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-tap-highlight-color: transparent;
        }

        body.light-mode .volume-button {
            border: 1px solid rgba(0,0,0,0.2);
        }

        .volume-button:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
        }

        body.light-mode .volume-button:hover {
            background: rgba(0,0,0,0.05);
            border-color: rgba(0,0,0,0.3);
        }

        .volume-button.muted {
            background: rgba(255,0,0,0.2);
            border-color: rgba(255,0,0,0.4);
            color: #ff4444;
        }

        .volume-slider {
            width: 80px;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            outline: none;
            cursor: pointer;
            -webkit-appearance: none;
        }

        body.light-mode .volume-slider {
            background: rgba(0,0,0,0.2);
        }

        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            background: var(--accent);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }

        .volume-slider::-moz-range-thumb {
            width: 16px;
            height: 16px;
            background: var(--accent);
            border-radius: 50%;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 16px;
            text-align: left;
        }

        .info-card {
            padding: 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            min-height: 80px;
            transition: background 0.3s ease, border 0.3s ease;
        }

        body.light-mode .info-card {
            background: rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.08);
        }

        .info-card span {
            display: block;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
        }

        .info-card strong {
            display: block;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            width: 100%;
            min-height: 24px;
        }

        /* Popup Modal */
        .popup-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .popup-content {
            background: var(--bg-secondary);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--border-radius);
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            animation: popupFadeIn 0.3s ease;
        }

        body.light-mode .popup-content {
            background: var(--bg-secondary);
            border: 1px solid rgba(0,0,0,0.1);
        }

        @keyframes popupFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .popup-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        body.light-mode .popup-header {
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .popup-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .popup-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 32px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            line-height: 1;
        }

        .popup-close:hover {
            color: var(--text-primary);
            transform: rotate(90deg);
        }

        .popup-body {
            padding: 24px;
            overflow-y: auto;
            max-height: calc(80vh - 80px);
        }

        .popup-track-item {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        body.light-mode .popup-track-item {
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .popup-track-item:hover {
            background: rgba(255,255,255,0.05);
        }

        body.light-mode .popup-track-item:hover {
            background: rgba(0,0,0,0.05);
        }

        .popup-track-item:last-child {
            border-bottom: none;
        }

        .popup-track-info {
            flex: 1;
            min-width: 0;
        }

        .popup-track-title {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .popup-track-artist {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .spotify-button {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            background: #000000;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #1DB954;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-tap-highlight-color: transparent;
            flex-shrink: 0;
        }

        body.light-mode .spotify-button {
            background: #000000;
            border: 1px solid rgba(0,0,0,0.3);
        }

        .spotify-button:hover {
            background: #1a1a1a;
            border-color: #1DB954;
            transform: translateY(-1px);
        }

        .spotify-button:active {
            transform: translateY(0);
        }

        .spotify-button svg {
            width: 16px;
            height: 16px;
            fill: #1DB954;
        }

        .player.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            border-radius: 0;
            padding: 40px;
        }

        .player.fullscreen .layout {
            height: 100%;
            justify-content: center;
        }

        .player.fullscreen .cover-art {
            width: clamp(300px, 30vw, 500px);
            height: clamp(300px, 30vw, 500px);
        }

        .player.fullscreen .track-meta h1 {
            font-size: clamp(32px, 5vw, 64px);
        }

        .player.fullscreen .track-meta h2 {
            font-size: clamp(20px, 3vw, 36px);
        }

        .player.fullscreen .play-toggle {
            width: 80px;
            height: 80px;
            font-size: 36px;
        }

        .player.fullscreen .info-grid {
            grid-template-columns: repeat(4, 1fr);
            max-width: 600px;
        }

        .player-footer {
            width: 100%;
            max-width: min(100%, 960px);
            text-align: center;
            padding: 24px 0;
            margin-top: 24px;
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 16px;
            width: 100%;
        }

        .footer-top {
            width: 100%;
            text-align: center;
            margin-bottom: 8px;
        }

        .footer-top a {
            color: #821e61;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .footer-top a:hover {
            opacity: 0.8;
        }

        .footer-bottom {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            width: 100%;
        }

        .footer-left {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            justify-content: flex-start;
            font-size: 11px;
            color: var(--text-secondary);
        }

        .footer-logo-small {
            width: auto;
            height: 38px;
            border-radius: 5px;
            display: block;
        }

        .footer-divider {
            width: 1px;
            height: 75px;
            background: var(--text-secondary);
            opacity: 0.3;
        }

        .footer-right {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 11px;
            color: var(--text-secondary);
        }

        .footer-logo-network {
            width: auto;
            height: 38px;
            border-radius: 5px;
            display: block;
        }

        .footer-link {
            display: inline-block;
            text-decoration: none;
            transition: transform 0.3s ease, opacity 0.3s ease;
            color: inherit;
        }

        .footer-link:hover {
            opacity: 0.8;
        }

        .footer-logo {
            height: 40px;
            border-radius: 5px;
            display: block;
        }

        .footer-text {
            font-size: 12px;
            color: var(--text-secondary);
            margin: 0;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .player {
                padding: 16px;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }

            .footer-bottom {
                flex-direction: row;
                gap: 12px;
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .footer-left {
                justify-content: center;
                flex-direction: column;
                gap: 8px;
                align-items: center;
                min-width: 0;
                flex: 1 1 auto;
            }

            .footer-right {
                justify-content: center;
                flex-direction: column;
                gap: 8px;
                align-items: center;
                min-width: 0;
                flex: 1 1 auto;
            }

            .footer-left span,
            .footer-right span {
                white-space: nowrap;
                font-size: 10px;
            }

            .footer-divider {
                display: block;
                height: 50px;
                width: 1px;
            }

            .theme-toggle {
                top: 16px;
                right: 16px;
                width: 36px;
                height: 36px;
            }

            .layout {
                flex-direction: column;
                text-align: center;
                gap: 24px;
            }

            .cover-art {
                width: clamp(180px, 40vw, 240px);
                height: clamp(180px, 40vw, 240px);
            }

            .track-meta {
                align-items: center;
            }

            .badge {
                margin: 0 auto;
            }

            .info-grid {
                text-align: left;
                gap: 10px;
            }

            .play-toggle {
                width: 70px;
                height: 70px;
                font-size: 30px;
            }

            .controls {
                justify-content: center;
                flex-wrap: wrap;
            }

            .live-indicator {
                margin-left: 0;
                margin-top: 8px;
            }

            .volume-controls {
                margin-left: 0;
                margin-top: 8px;
            }

            .info-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 480px) {
            .player {
                padding: 12px;
            }

            .info-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                text-align: left;
            }

            .footer-logo {
                height: 32px;
            }

            .footer-text {
                font-size: 11px;
            }

            .controls {
                gap: 12px;
                justify-content: center;
            }

            .play-toggle {
                width: 68px;
                height: 68px;
                font-size: 28px;
            }

            .secondary {
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
<main class="player" id="radioPlayer"
      data-endpoint="https://sssx.radyosfer.com/corlufm/stats?sid=1"
      data-stream="https://sssx.radyosfer.com/corlufm/stream"
      data-default-art="https://kolaypanel.s3.eu-central-1.amazonaws.com/hello/uploads/2025/10/29082743/corlu-fm-logo-2.webp">
    <button class="theme-toggle" id="themeToggle" aria-label="Tema Değiştir">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            <path d="M19 3v4"/>
            <path d="M21 5h-4"/>
            <path d="M17 21a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/>
            <path d="m16 7 1 1"/>
        </svg>
    </button>
    <div class="layout">
        <section class="cover-art" id="coverArt"
                 style="background-image: url('https://kolaypanel.s3.eu-central-1.amazonaws.com/hello/uploads/2025/10/29082743/corlu-fm-logo-2.webp');">
        </section>
        <section class="track-meta">
            <span class="badge on-air" id="onAirBadge">
                ON AIR
            </span>
            <h1 id="trackTitle">Canlı Yayın</h1>
            <h2 id="trackArtist">Çorlu FM</h2>
            <div class="controls">
                <button class="play-toggle" id="playToggle" aria-label="Yayını Başlat">
                    &#9658;
                </button>
                <button class="secondary" id="fullscreenToggle" aria-label="Tam ekran">
                    ⤢ Tam Ekran
                </button>
                <div class="volume-controls">
                    <button class="volume-button" id="muteButton" aria-label="Sesi Aç/Kapat">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 5L6 9H2v6h4l5 4V5z"/>
                            <path id="volumeWavePath1" d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>
                            <path id="volumeWavePath2" d=""/>
                        </svg>
                    </button>
                    <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="100" aria-label="Ses Seviyesi">
                </div>
                <div class="live-indicator" id="stereoIndicator">
                    <span class="pulse-dot"></span>
                    STEREO
                </div>
            </div>
            <div class="info-grid">
                <div class="info-card">
                    <span>Anlık Dinleyici</span>
                    <strong id="listenerCount">0</strong>
                </div>
                <div class="info-card">
                    <span>En Çok Dinleyici</span>
                    <strong id="peakListeners">0</strong>
                </div>
                <div class="info-card" id="lastPlayedCard" style="cursor: pointer; position: relative;">
                    <span>Son Çalan</span>
                    <strong id="lastPlayedArtist">-</strong>
                    <strong id="lastPlayedTitle" style="font-size: 14px; margin-top: 4px; opacity: 0.7;">-</strong>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; top: 8px; right: 8px; opacity: 0.3;">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                </div>
            </div>
        </section>
    </div>
    <audio id="radioAudio" preload="none" playsinline>
        <source src="https://sssx.radyosfer.com/corlufm/stream" type="audio/mpeg">
        Tarayıcınız canlı yayını çalamıyor.
    </audio>
</main>

<footer class="player-footer">
    <div class="footer-content">
        <div class="footer-top">
            <a href="https://www.corlufm.com" target="_blank" rel="noopener noreferrer">www.corlufm.com</a>
        </div>
        <div class="footer-bottom">
            <div class="footer-left">
            <a href="https://corlufm.com" target="_blank" rel="noopener noreferrer" class="footer-link">
                <img src="https://kolaypanel.s3.eu-central-1.amazonaws.com/hello/uploads/2025/10/29082743/corlu-fm-logo-2.webp" alt="Çorlu FM" class="footer-logo-small" width="auto" height="38">
            </a>
            <span>Copyright Çorlu FM.</span>
            <span>All rights reserved.</span>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-right">
            <a href="https://thenetwork.com.tr" target="_blank" rel="noopener noreferrer" class="footer-link">
                <img src="https://thenetwork.com.tr/wp-content/uploads/2025/10/thenetworktr_profile_bg.png" alt="The Network" class="footer-logo-network" width="auto" height="38">
            </a>
            <span>design by</span>
            <span>The Network</span>
        </div>
        </div>
    </div>
</footer>

<!-- Popup Modal -->
<div id="popupModal" class="popup-modal" style="display: none;">
    <div class="popup-content">
        <div class="popup-header">
            <h3>Son Çalan Şarkılar</h3>
            <button class="popup-close" id="popupClose">&times;</button>
        </div>
        <div class="popup-body" id="popupBody">
            <p style="text-align: center; color: var(--text-secondary);">Yükleniyor...</p>
        </div>
    </div>
</div>

<script>
    const playerEl = document.getElementById('radioPlayer');
    const playToggleEl = document.getElementById('playToggle');
    const fullscreenToggleEl = document.getElementById('fullscreenToggle');
    const radioAudioEl = document.getElementById('radioAudio');
    const trackTitleEl = document.getElementById('trackTitle');
    const trackArtistEl = document.getElementById('trackArtist');
    const listenerCountEl = document.getElementById('listenerCount');
    const peakListenersEl = document.getElementById('peakListeners');
    const coverArtEl = document.getElementById('coverArt');
    const muteButtonEl = document.getElementById('muteButton');
    const volumeSliderEl = document.getElementById('volumeSlider');
    const themeToggleEl = document.getElementById('themeToggle');
    const stereoIndicatorEl = document.getElementById('stereoIndicator');
    const onAirBadgeEl = document.getElementById('onAirBadge');
    const lastPlayedCard = document.getElementById('lastPlayedCard');
    const lastPlayedArtist = document.getElementById('lastPlayedArtist');
    const lastPlayedTitle = document.getElementById('lastPlayedTitle');
    const popupModal = document.getElementById('popupModal');
    const popupClose = document.getElementById('popupClose');
    const popupBody = document.getElementById('popupBody');

    let isPlaying = false;
    let isFullscreen = false;
    let isMuted = false;
    let savedVolume = 100;

    // SVG ikonlar
    const moonIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
    const sunIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>`;
    
    // Volume SVG ikonları
    const volumeOnIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>`;
    const volumeOffIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M22 9l-6 6M16 9l6 6"/></svg>`;
    
    // İlk yüklemede ses açık ikonunu göster
    muteButtonEl.innerHTML = volumeOnIcon;

    // Title Case - Türkçe karakterleri düzgün işleyen
    const toTitleCase = (str) => {
        if (!str) return '';
        
        const words = str.split(/\s+/);
        const conjunctions = ['ve', 'ile', 'ya', 'ya da', 'de', 'da', 'ki', 'mi', 'mu', 'mü'];
        
        return words.map((word, index) => {
            // İlk kelime her zaman büyük
            if (index === 0) {
                return word.charAt(0).toLocaleUpperCase('tr-TR') + word.slice(1).toLocaleLowerCase('tr-TR');
            }
            // Bağlaçlar küçük kalmalı
            if (conjunctions.includes(word.toLowerCase())) {
                return word.toLowerCase();
            }
            // FM gibi kısa kısaltmaları koru
            if (word.length <= 4 && word === word.toUpperCase()) {
                return word;
            }
            // İlk harfi büyük, kalanını küçük yap
            return word.charAt(0).toLocaleUpperCase('tr-TR') + word.slice(1).toLocaleLowerCase('tr-TR');
        }).join(' ');
    };

    // Tema kontrolü
    const currentTheme = localStorage.getItem('theme') || 'dark';
    if (currentTheme === 'light') {
        document.body.classList.add('light-mode');
        document.documentElement.classList.add('light-mode');
        themeToggleEl.innerHTML = sunIcon;
    } else {
        themeToggleEl.innerHTML = moonIcon;
    }

    // Logo kontrolü
    if (coverArtEl.style.backgroundImage.includes('corlu-fm-logo') || coverArtEl.style.backgroundImage.includes('logo2')) {
        coverArtEl.classList.add('is-logo');
    }

    // Tema değiştir
    const toggleTheme = () => {
        const isLight = document.body.classList.contains('light-mode');
        if (isLight) {
            document.body.classList.remove('light-mode');
            document.documentElement.classList.remove('light-mode');
            themeToggleEl.innerHTML = moonIcon;
            localStorage.setItem('theme', 'dark');
        } else {
            document.body.classList.add('light-mode');
            document.documentElement.classList.add('light-mode');
            themeToggleEl.innerHTML = sunIcon;
            localStorage.setItem('theme', 'light');
        }
        
        const currentBg = coverArtEl.style.backgroundImage;
        if (currentBg && (currentBg.includes('corlu-fm-logo') || currentBg.includes('logo2'))) {
            coverArtEl.classList.add('is-logo');
        } else {
            coverArtEl.classList.remove('is-logo');
        }
    };

    themeToggleEl.addEventListener('click', toggleTheme);

    // Verileri PHP backend'den çek
    const updateData = async () => {
        try {
            const backendUrl = '?action=getStationData';
            const response = await fetch(backendUrl);
            const result = await response.json();
            
            if (result.success && result.data) {
                const data = result.data;
                
                // Başlık ve sanatçı
                if (data.title && data.title !== 'CORLU FM' && data.title.toUpperCase() !== 'CORLU FM') {
                    trackTitleEl.textContent = toTitleCase(data.title);
                } else {
                    trackTitleEl.textContent = "Canlı Yayın";
                }
                
                if (data.artist && data.artist !== 'CORLU FM' && data.artist.toUpperCase() !== 'CORLU FM') {
                    trackArtistEl.textContent = toTitleCase(data.artist);
                } else {
                    trackArtistEl.textContent = "Çorlu FM";
                }
                
                // Dinleyici sayıları
                listenerCountEl.textContent = data.listeners || '0';
                peakListenersEl.textContent = data.peakListeners || '0';
                
                // Kapak görseli
                const artUrl = data.artworkUrl || playerEl.dataset.defaultArt;
                coverArtEl.style.backgroundImage = `url('${artUrl}')`;
                
                // Logo kontrolü
                if (artUrl.includes('corlu-fm-logo') || artUrl.includes('logo2')) {
                    coverArtEl.classList.add('is-logo');
                } else {
                    coverArtEl.classList.remove('is-logo');
                }
                
                // YouTube video arka planı DEVRE DIŞI
                // Video background özelliği kaldırıldı
            } else {
                // Fallback veriler
                trackTitleEl.textContent = "Canlı Yayın";
                trackArtistEl.textContent = "Çorlu FM";
                listenerCountEl.textContent = "0";
                peakListenersEl.textContent = "0";
                coverArtEl.style.backgroundImage = `url('${playerEl.dataset.defaultArt}')`;
                coverArtEl.classList.add('is-logo');
            }
        } catch (error) {
            // Fallback veriler
            trackTitleEl.textContent = "Canlı Yayın";
            trackArtistEl.textContent = "Çorlu FM";
            listenerCountEl.textContent = "0";
            peakListenersEl.textContent = "0";
        }
    };

    // Play/Pause toggle
    const togglePlay = () => {
        if (isPlaying) {
            radioAudioEl.pause();
            playToggleEl.innerHTML = '&#9658;';
            isPlaying = false;
            stereoIndicatorEl.classList.remove('playing');
            onAirBadgeEl.classList.remove('playing');
        } else {
            radioAudioEl.play().catch(() => {});
            playToggleEl.innerHTML = '&#10074;&#10074;';
            isPlaying = true;
            stereoIndicatorEl.classList.add('playing');
            onAirBadgeEl.classList.add('playing');
        }
    };

    // Fullscreen toggle
    const toggleFullscreen = () => {
        if (isFullscreen) {
            playerEl.classList.remove('fullscreen');
            fullscreenToggleEl.textContent = '⤢ Tam Ekran';
            isFullscreen = false;
        } else {
            playerEl.classList.add('fullscreen');
            fullscreenToggleEl.textContent = '⤡ Küçült';
            isFullscreen = true;
        }
    };

    // Mute toggle
    const toggleMute = () => {
        if (isMuted || radioAudioEl.muted) {
            radioAudioEl.muted = false;
            radioAudioEl.volume = savedVolume / 100;
            volumeSliderEl.value = savedVolume;
            muteButtonEl.innerHTML = volumeOnIcon;
            muteButtonEl.classList.remove('muted');
            isMuted = false;
        } else {
            savedVolume = radioAudioEl.volume * 100;
            radioAudioEl.muted = true;
            volumeSliderEl.value = 0;
            muteButtonEl.innerHTML = volumeOffIcon;
            muteButtonEl.classList.add('muted');
            isMuted = true;
        }
    };

    // Volume control
    const updateVolume = (volume) => {
        radioAudioEl.volume = volume / 100;
        radioAudioEl.muted = false;
        if (volume > 0) {
            isMuted = false;
            muteButtonEl.innerHTML = volumeOnIcon;
            muteButtonEl.classList.remove('muted');
        } else {
            isMuted = true;
            muteButtonEl.innerHTML = volumeOffIcon;
            muteButtonEl.classList.add('muted');
        }
    };

    // Event listeners
    playToggleEl.addEventListener('click', togglePlay);
    fullscreenToggleEl.addEventListener('click', toggleFullscreen);
    muteButtonEl.addEventListener('click', toggleMute);
    volumeSliderEl.addEventListener('input', (e) => updateVolume(e.target.value));
    
    // Audio event listeners
    radioAudioEl.addEventListener('play', () => {
        stereoIndicatorEl.classList.add('playing');
        onAirBadgeEl.classList.add('playing');
    });
    
    radioAudioEl.addEventListener('pause', () => {
        stereoIndicatorEl.classList.remove('playing');
        onAirBadgeEl.classList.remove('playing');
    });
    
    radioAudioEl.addEventListener('ended', () => {
        stereoIndicatorEl.classList.remove('playing');
        onAirBadgeEl.classList.remove('playing');
    });

    // Son çalan şarkıları çek
    let recentlyPlayedTracks = [];
    
    const fetchRecentlyPlayed = async () => {
        try {
            const response = await fetch('?action=getRecentlyPlayed');
            const result = await response.json();
            
            if (result.success && result.data && result.data.length > 0) {
                recentlyPlayedTracks = result.data;
                
                // İlk şarkıyı kutucukta göster
                if (result.data[0]) {
                    const artist = result.data[0].artist || 'Çorlu FM';
                    const title = result.data[0].title || '-';
                    
                    // Artist varsa göster, yoksa gizle
                    if (result.data[0].artist) {
                        lastPlayedArtist.textContent = artist;
                        lastPlayedArtist.style.display = 'block';
                    } else {
                        lastPlayedArtist.style.display = 'none';
                    }
                    
                    lastPlayedTitle.textContent = title;
                }
            }
        } catch (error) {
            console.error('Son çalan şarkılar yüklenemedi:', error);
        }
    };
    
    // Popup aç/kapa
    const openPopup = () => {
        popupModal.style.display = 'flex';
        renderPopupContent();
    };
    
    const closePopup = () => {
        popupModal.style.display = 'none';
    };
    
    const renderPopupContent = () => {
        popupBody.innerHTML = ''; // Temizle
        
        if (recentlyPlayedTracks.length === 0) {
            const emptyMsg = document.createElement('p');
            emptyMsg.style.textAlign = 'center';
            emptyMsg.style.color = 'var(--text-secondary)';
            emptyMsg.textContent = 'Henüz şarkı çalınmamış.';
            popupBody.appendChild(emptyMsg);
            return;
        }
        
        recentlyPlayedTracks.forEach(track => {
            // Query oluştur
            const searchQuery = track.artist && track.artist !== 'Çorlu FM' && track.artist
                ? `${track.artist} ${track.title}` 
                : track.title;
            
            // Item container
            const item = document.createElement('div');
            item.className = 'popup-track-item';
            
            // Info container (sol taraf)
            const infoContainer = document.createElement('div');
            infoContainer.className = 'popup-track-info';
            
            // Title
            const title = document.createElement('div');
            title.className = 'popup-track-title';
            title.textContent = track.title;
            infoContainer.appendChild(title);
            
            // Artist
            const artist = document.createElement('div');
            artist.className = 'popup-track-artist';
            artist.textContent = track.artist || 'Çorlu FM';
            infoContainer.appendChild(artist);
            
            item.appendChild(infoContainer);
            
            // Spotify Button (sağ taraf)
            const button = document.createElement('button');
            button.className = 'spotify-button';
            button.dataset.query = searchQuery;
            button.innerHTML = `
                <svg viewBox="0 0 24 24"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.361 1.56-.241.421-.78.599-1.44.36z"/></svg>
                Spotify'da Dinle
            `;
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                openInSpotify(searchQuery);
            });
            item.appendChild(button);
            
            popupBody.appendChild(item);
        });
    };
    
    // Spotify deep link
    const openInSpotify = (query) => {
        // Query'yi encode et
        const encodedQuery = encodeURIComponent(query);
        
        // Mobile detection
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        
        if (isMobile) {
            // Deep link kullan (uygulamayı aç)
            const deepLink = `spotify:search:${encodedQuery}`;
            window.location.href = deepLink;
            
            // Fallback: Uygulama yoksa web player
            setTimeout(() => {
                window.open(`https://open.spotify.com/search/${encodedQuery}`, '_blank');
            }, 1000);
        } else {
            // Desktop: Yeni tab'da web player aç
            window.open(`https://open.spotify.com/search/${encodedQuery}`, '_blank');
        }
    };
    
    // Event listeners
    lastPlayedCard.addEventListener('click', openPopup);
    popupClose.addEventListener('click', closePopup);
    popupModal.addEventListener('click', (e) => {
        if (e.target === popupModal) {
            closePopup();
        }
    });
    
    // İlk yüklemede veriyi çek
    updateData();
    fetchRecentlyPlayed();
    
    // Şarkı değişiklikleri daha sık kontrol et (kritik)
    setInterval(updateData, 10000);
    
    // Son çalan şarkılar daha az sıklıkla güncelle (non-critical)
    setInterval(() => {
        // Sadece idle time'da çalış
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => {
                fetchRecentlyPlayed();
            }, { timeout: 10000 });
        } else {
            fetchRecentlyPlayed();
        }
    }, 30000);

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.code === 'Space' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            togglePlay();
        }
        if (e.code === 'KeyF' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            toggleFullscreen();
        }
        if (e.code === 'KeyM' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            toggleMute();
        }
    });
</script>
</body>
</html>
