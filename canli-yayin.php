<?php
// PHP Backend - XML verisi ve albüm kapağı çekme
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

        $artist = $serverTitle;
        $title = $rawSong;

        // Artist ve title'ı parse et
        if (strpos($rawSong, ' - ') !== false) {
            list($parsedArtist, $parsedTitle) = explode(' - ', $rawSong, 2);
            $artist = trim($parsedArtist);
            $title = trim($parsedTitle);
        } else if (!empty($rawSong)) {
            $title = $rawSong;
        }
        
        // iTunes API'den albüm kapağı al
        $artworkUrl = null;
        if (!empty($title) && $title !== 'CORLU FM' && $artist !== 'CORLU FM') {
            try {
                $itunesQuery = urlencode($title);
                $itunesUrl = "https://itunes.apple.com/search?term={$itunesQuery}&media=music&limit=1";
                $itunesData = @file_get_contents($itunesUrl);
                if ($itunesData) {
                    $itunesJson = json_decode($itunesData, true);
                    if (!empty($itunesJson['results']) && !empty($itunesJson['results'][0]['artworkUrl100'])) {
                        $artworkUrl = str_replace('100x100bb.jpg', '512x512bb.jpg', $itunesJson['results'][0]['artworkUrl100']);
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
                'status' => 'Canlı Yayın',
                'artworkUrl' => null
            ]
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
    <link rel="preconnect" href="https://fonts.cdnfonts.com">
    <link href="https://fonts.cdnfonts.com/css/sofia-pro" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="http://www.corlufm.com/wp-content/themes/corlufm/assets/images/logo.png">
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
            font-family: 'Sofia Pro', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
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

        body.light-mode .cover-art.is-logo {
            background-color: #ffffff !important;
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
            padding: 8px 16px;
            background: rgba(29,185,84,0.15);
            border: 1px solid rgba(29,185,84,0.3);
            border-radius: 24px;
            font-size: 14px;
            font-weight: 500;
            color: var(--accent);
            width: fit-content;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }

        .track-meta h1 {
            font-size: clamp(24px, 4vw, 42px);
            line-height: 1.05;
            font-weight: 500;
            margin: 0;
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
            font-weight: 400;
            color: var(--text-secondary);
            margin: 0;
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
            font-size: 20px;
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
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .info-card {
            padding: 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            text-align: center;
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
        }

        .info-card strong {
            display: block;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
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
            font-size: 28px;
        }

        .player.fullscreen .info-grid {
            grid-template-columns: repeat(4, 1fr);
            max-width: 600px;
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .player {
                padding: 16px;
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
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .player {
                padding: 12px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .controls {
                gap: 12px;
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
      data-default-art="http://www.corlufm.com/wp-content/themes/corlufm/assets/images/logo.png">
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
                 style="background-image: url('http://www.corlufm.com/wp-content/themes/corlufm/assets/images/logo.png');">
        </section>
        <section class="track-meta">
            <span class="badge">
                <span class="pulse-dot"></span>
                Canlı Yayın
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
                        🔊
                    </button>
                    <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="100" aria-label="Ses Seviyesi">
                </div>
                <div class="live-indicator">
                    <span class="pulse-dot"></span>
                    Full HD Ses
                </div>
            </div>
            <div class="info-grid">
                <div class="info-card">
                    <span>Dinleyiciler</span>
                    <strong id="listenerCount">0</strong>
                </div>
                <div class="info-card">
                    <span>Radyo</span>
                    <strong id="stationName">Çorlu FM</strong>
                </div>
            </div>
        </section>
    </div>
    <audio id="radioAudio" preload="none" playsinline>
        <source src="https://sssx.radyosfer.com/corlufm/stream" type="audio/mpeg">
        Tarayıcınız canlı yayını çalamıyor.
    </audio>
</main>

<script>
    const playerEl = document.getElementById('radioPlayer');
    const playToggleEl = document.getElementById('playToggle');
    const fullscreenToggleEl = document.getElementById('fullscreenToggle');
    const radioAudioEl = document.getElementById('radioAudio');
    const trackTitleEl = document.getElementById('trackTitle');
    const trackArtistEl = document.getElementById('trackArtist');
    const listenerCountEl = document.getElementById('listenerCount');
    const stationNameEl = document.getElementById('stationName');
    const coverArtEl = document.getElementById('coverArt');
    const muteButtonEl = document.getElementById('muteButton');
    const volumeSliderEl = document.getElementById('volumeSlider');
    const themeToggleEl = document.getElementById('themeToggle');

    let isPlaying = false;
    let isFullscreen = false;
    let isMuted = false;
    let savedVolume = 100;

    // SVG ikonlar
    const moonIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
    const sunIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>`;

    // Title Case
    const toTitleCase = (str) => {
        if (!str) return '';
        return str.replace(/\w\S*/g, (txt) => {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
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
    if (coverArtEl.style.backgroundImage.includes('logo.png')) {
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
        if (currentBg && currentBg.includes('logo.png')) {
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
                
                // Dinleyici sayısı
                listenerCountEl.textContent = data.listeners || '0';
                stationNameEl.textContent = "Çorlu FM";
                
                // Kapak görseli
                const artUrl = data.artworkUrl || playerEl.dataset.defaultArt;
                coverArtEl.style.backgroundImage = `url('${artUrl}')`;
                
                // Logo kontrolü
                if (artUrl.includes('logo.png')) {
                    coverArtEl.classList.add('is-logo');
                } else {
                    coverArtEl.classList.remove('is-logo');
                }
            } else {
                // Fallback veriler
                trackTitleEl.textContent = "Canlı Yayın";
                trackArtistEl.textContent = "Çorlu FM";
                listenerCountEl.textContent = "0";
                stationNameEl.textContent = "Çorlu FM";
                coverArtEl.style.backgroundImage = `url('${playerEl.dataset.defaultArt}')`;
                coverArtEl.classList.add('is-logo');
            }
        } catch (error) {
            // Fallback veriler
            trackTitleEl.textContent = "Canlı Yayın";
            trackArtistEl.textContent = "Çorlu FM";
            listenerCountEl.textContent = "0";
            stationNameEl.textContent = "Çorlu FM";
        }
    };

    // Play/Pause toggle
    const togglePlay = () => {
        if (isPlaying) {
            radioAudioEl.pause();
            playToggleEl.innerHTML = '&#9658;';
            isPlaying = false;
        } else {
            radioAudioEl.play().catch(() => {});
            playToggleEl.innerHTML = '&#10074;&#10074;';
            isPlaying = true;
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
            muteButtonEl.textContent = '🔊';
            muteButtonEl.classList.remove('muted');
            isMuted = false;
        } else {
            savedVolume = radioAudioEl.volume * 100;
            radioAudioEl.muted = true;
            volumeSliderEl.value = 0;
            muteButtonEl.textContent = '🔇';
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
            muteButtonEl.textContent = '🔊';
            muteButtonEl.classList.remove('muted');
        } else {
            isMuted = true;
            muteButtonEl.textContent = '🔇';
            muteButtonEl.classList.add('muted');
        }
    };

    // Event listeners
    playToggleEl.addEventListener('click', togglePlay);
    fullscreenToggleEl.addEventListener('click', toggleFullscreen);
    muteButtonEl.addEventListener('click', toggleMute);
    volumeSliderEl.addEventListener('input', (e) => updateVolume(e.target.value));

    // İlk yüklemede veriyi çek
    updateData();
    setInterval(updateData, 10000);

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
