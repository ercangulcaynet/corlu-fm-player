<?php
// PHP kısmı - XML verisi çekme
if (isset($_GET['action']) && $_GET['action'] === 'getStationData') {
    header('Content-Type: application/json');
    
    try {
        $xmlUrl = 'https://sssx.radyosfer.com/corlufm/stats?sid=1';
        $xmlData = @simplexml_load_file($xmlUrl);
        
        if ($xmlData === false) {
            throw new Exception('XML verisi yüklenemedi');
        }
        
        $rawSong = (string)$xmlData->SONGTITLE;
        $serverTitle = (string)$xmlData->SERVERTITLE;
        $listeners = (int)$xmlData->CURRENTLISTENERS;

        $artist = $serverTitle; // Default artist to server title
        $title = $rawSong;

        // Attempt to parse artist and title from SONGTITLE
        if (strpos($rawSong, ' - ') !== false) {
            list($parsedArtist, $parsedTitle) = explode(' - ', $rawSong, 2);
            $artist = trim($parsedArtist);
            $title = trim($parsedTitle);
        } else if (!empty($rawSong)) {
            // If no ' - ' separator, assume rawSong is the title and artist is serverTitle
            $title = $rawSong;
        }
        
        $data = [
            'title' => $title,
            'artist' => $artist,
            'listeners' => $listeners,
            'status' => 'Canlı Yayın',
            'timestamp' => time()
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
                'status' => 'Canlı Yayın'
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
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
        }

        .player {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 24px;
            width: min(100%, 960px);
            padding: 24px;
            border-radius: var(--border-radius);
            background: linear-gradient(135deg, rgba(24,24,24,0.95), rgba(12,12,12,0.98));
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(12px);
            transition: transform 0.4s ease;
            overflow: hidden;
        }

        .player:hover {
            transform: translateY(-4px);
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
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
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
        }

        .secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
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
        }

        .volume-button:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
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
            .layout {
                flex-direction: column;
                text-align: center;
                gap: 24px;
            }

            .cover-art {
                width: clamp(180px, 40vw, 240px);
                height: clamp(180px, 40vw, 240px);
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
                padding: 16px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<main class="player" id="radioPlayer"
      data-endpoint="https://sssx.radyosfer.com/corlufm/stats?sid=1"
      data-stream="https://sssx.radyosfer.com/corlufm/stream"
      data-default-art="http://www.corlufm.com/wp-content/themes/corlufm/assets/images/logo.png">
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
    <audio id="radioAudio" preload="none" playsinline autoplay muted>
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

    let isPlaying = false;
    let isFullscreen = false;
    let isMuted = false;
    let savedVolume = 100;

    // Title Case uygula
    const toTitleCase = (str) => {
        if (!str) return '';
        return str.replace(/\w\S*/g, (txt) => {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    };

    // Albüm görseli cache
    const albumArtCache = {};
    const getAlbumArt = async (artist, title) => {
        const key = `${artist} - ${title}`;
        if (albumArtCache[key]) {
            return albumArtCache[key];
        }

        try {
            const query = encodeURIComponent(`${artist} ${title}`);
            const response = await fetch(`https://itunes.apple.com/search?term=${query}&media=music&limit=1`);
            const data = await response.json();
            
            if (data.results && data.results.length > 0) {
                const artUrl = data.results[0].artworkUrl100.replace('100x100bb.jpg', '512x512bb.jpg');
                albumArtCache[key] = artUrl;
                return artUrl;
            }
        } catch (error) {
            console.warn('Albüm görseli alınamadı:', error);
        }
        return playerEl.dataset.defaultArt; // Fallback to default logo
    };

    // XML verilerini çek ve göster
    const updateData = async () => {
        try {
            const response = await fetch('?action=getStationData');
            const data = await response.json();
            
            if (data.success) {
                // Title Case uygula
                if (data.data.artist.toUpperCase() === "CORLU FM" || data.data.title.toUpperCase() === "CORLU FM" || data.data.title === "Çorlu FM") { trackTitleEl.textContent = "Çorlu FM"; } else { trackTitleEl.textContent = toTitleCase(data.data.title); }
                if (data.data.artist.toUpperCase() === "CORLU FM") { trackArtistEl.textContent = "Çorlu FM"; } else { trackArtistEl.textContent = toTitleCase(data.data.artist); }
                listenerCountEl.textContent = data.data.listeners;
                stationNameEl.textContent = "Çorlu FM";
                
                // Kapak görseli güncelle
                const artUrl = await getAlbumArt(data.data.artist, data.data.title);
                coverArtEl.style.backgroundImage = `url('${artUrl}')`;
            }
        } catch (error) {
            console.log('Veri güncellenemedi:', error);
        }
    };

    // Play/Pause toggle
    const togglePlay = () => {
        if (isPlaying) {
            radioAudioEl.pause();
            playToggleEl.innerHTML = '&#9658;';
            isPlaying = false;
        } else {
            radioAudioEl.play().catch(error => {
                console.log('Otomatik çalma engellendi:', error);
            });
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
        radioAudioEl.muted = false; // Volume değiştiğinde unmute yap
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

    // Auto-play on load
    window.addEventListener('load', () => {
        // Ses seviyesini ayarla
        radioAudioEl.volume = 1.0;
        
        // Otomatik çalmayı dene (muted ile başla)
        const tryAutoPlay = () => {
            radioAudioEl.muted = true; // Muted ile başla
            radioAudioEl.play().then(() => {
                console.log('Otomatik çalma başarılı (muted)');
                // 1 saniye sonra unmute yap
                setTimeout(() => {
                    radioAudioEl.muted = false;
                    playToggleEl.innerHTML = '&#10074;&#10074;';
                    isPlaying = true;
                    console.log('Ses açıldı');
                }, 1000);
            }).catch(error => {
                console.log('Otomatik çalma engellendi:', error);
                // Kullanıcı etkileşimi bekle
                document.addEventListener('click', () => {
                    radioAudioEl.muted = false;
                    radioAudioEl.play().then(() => {
                        playToggleEl.innerHTML = '&#10074;&#10074;';
                        isPlaying = true;
                    });
                }, { once: true });
            });
        };
        
        // Hemen dene
        tryAutoPlay();
    });

    // Update data every 10 seconds
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
