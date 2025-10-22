<?php
declare(strict_types=1);

const STREAM_URL = 'https://sssx.radyosfer.com/corlufm/stream';
const STATS_URL = 'https://sssx.radyosfer.com/corlufm/stats?sid=1';
const DEFAULT_ART = 'http://www.corlufm.com/wp-content/themes/corlufm/assets/images/logo.png';

/**
 * Fetches and normalises Shoutcast statistics.
 *
 * @return array<string, mixed>
 */
function fetchStats(): array
{
    $base = [
        'songTitle'    => 'Canlı Yayın',
        'artist'       => '',
        'track'        => 'Çorlu FM',
        'rawSongTitle' => '',
        'listeners'    => 0,
        'serverTitle'  => 'Çorlu FM',
        'bitrate'      => '',
        'isOnline'     => false,
        'albumArt'     => DEFAULT_ART,
    ];

    $xml = @simplexml_load_file(STATS_URL);

    if ($xml === false) {
        return $base;
    }

    $xml = (array) $xml;

    $rawSong = isset($xml['SONGTITLE']) ? trim((string) $xml['SONGTITLE']) : '';
    $songPieces = explode(' - ', $rawSong, 2);
    $artist = trim($songPieces[0] ?? '');
    $track = trim($songPieces[1] ?? ($artist !== '' ? '' : $rawSong));

    return [
        'songTitle'    => $track !== '' ? $track : ($artist !== '' ? $artist : 'Canlı Yayın'),
        'artist'       => $artist,
        'track'        => $track,
        'rawSongTitle' => $rawSong,
        'listeners'    => isset($xml['CURRENTLISTENERS']) ? (int) $xml['CURRENTLISTENERS'] : 0,
        'serverTitle'  => isset($xml['SERVERTITLE']) ? (string) $xml['SERVERTITLE'] : 'Çorlu FM',
        'bitrate'      => isset($xml['BITRATE']) ? (string) $xml['BITRATE'] : '',
        'isOnline'     => isset($xml['STREAMSTATUS']) ? ((int) $xml['STREAMSTATUS'] === 1) : false,
        'albumArt'     => DEFAULT_ART,
    ];
}

if (isset($_GET['action']) && $_GET['action'] === 'stats') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode(fetchStats(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$stats = fetchStats();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($stats['serverTitle'] ?? 'Çorlu FM', ENT_QUOTES, 'UTF-8'); ?> · Canlı Radyo Player</title>
    <style>
        :root {
            color-scheme: dark;
            --bg-primary: #121212;
            --bg-secondary: #181818;
            --bg-tertiary: #1f1f1f;
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
            font-family: 'Inter', 'Circular Std', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.7),
                inset 0 1px 0 rgba(255,255,255,0.02);
            backdrop-filter: blur(12px);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            overflow: hidden;
        }

        .player::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 20%, rgba(29,185,84,0.12), transparent 50%);
            z-index: 0;
        }

        .player:hover {
            transform: translateY(-4px);
            box-shadow:
                0 35px 80px -20px rgba(0,0,0,0.65),
                inset 0 1px 0 rgba(255,255,255,0.02);
        }

        .player.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            margin: 0;
            border-radius: 0;
            padding: 48px;
            z-index: 999;
        }

        .player.fullscreen .layout {
            height: calc(100vh - 96px);
        }

        .player.fullscreen .cover-art {
            flex: 0 0 40%;
        }

        .layout {
            display: flex;
            flex-direction: column;
            gap: 24px;
            position: relative;
            z-index: 1;
        }

        .cover-art {
            aspect-ratio: 1 / 1;
            border-radius: clamp(16px, 4vw, 24px);
            background-size: cover;
            background-position: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
            position: relative;
            overflow: hidden;
        }

        .cover-art::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 55%, rgba(0,0,0,0.35));
        }

        .track-meta {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .track-meta .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: rgba(29, 185, 84, 0.12);
            color: var(--accent);
            padding: 8px 18px;
            border-radius: 999px;
            font-size: 13px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            align-self: flex-start;
        }

        .track-meta h1 {
            font-size: clamp(24px, 4vw, 42px);
            line-height: 1.05;
        }

        .track-meta h2 {
            font-size: clamp(16px, 2.2vw, 20px);
            font-weight: 500;
            color: var(--text-secondary);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
        }

        .info-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: border-color 0.3s ease, transform 0.3s ease;
        }

        .info-card:hover {
            border-color: rgba(29,185,84,0.3);
            transform: translateY(-2px);
        }

        .info-card span {
            font-size: 13px;
            color: var(--text-secondary);
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .info-card strong {
            font-size: 18px;
        }

        .controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
        }

        .controls .play-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--accent);
            color: var(--bg-primary);
            border: none;
            cursor: pointer;
            font-size: 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            box-shadow: 0 10px 30px rgba(29,185,84,0.35);
        }

        .controls .play-toggle:hover {
            transform: scale(1.06);
            box-shadow: 0 14px 36px rgba(29,185,84,0.45);
        }

        .controls .play-toggle:active {
            transform: scale(0.96);
        }

        .controls .secondary {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.08);
            cursor: pointer;
            color: var(--text-primary);
            transition: border-color 0.3s ease, transform 0.3s ease;
            font-size: 14px;
        }

        .controls .secondary:hover {
            border-color: rgba(29,185,84,0.4);
            transform: translateY(-1px);
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            background: rgba(255,255,255,0.04);
            border-radius: 999px;
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--accent);
            box-shadow: 0 0 0 rgba(29, 185, 84, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.9);
                box-shadow: 0 0 0 0 rgba(29, 185, 84, 0.4);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(29, 185, 84, 0);
            }
            100% {
                transform: scale(0.9);
                box-shadow: 0 0 0 0 rgba(29, 185, 84, 0);
            }
        }

        .marquee {
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .marquee span {
            display: inline-block;
            min-width: 100%;
            animation: marquee 12s linear infinite;
        }

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-100%);
            }
        }

        audio {
            display: none;
        }

        @media (min-width: 820px) {
            .layout {
                flex-direction: row;
                align-items: center;
            }

            .cover-art {
                flex: 0 0 clamp(240px, 32vw, 320px);
            }

            .track-meta {
                flex: 1;
            }
        }

        @media (max-width: 560px) {
            body {
                padding: 16px;
            }

            .player {
                padding: 20px;
                gap: 20px;
            }

            .controls {
                gap: 12px;
            }

        }
    </style>
</head>
<body>
<main class="player" id="radioPlayer"
      data-endpoint="<?= htmlspecialchars(basename(__FILE__) . '?action=stats', ENT_QUOTES, 'UTF-8'); ?>"
      data-stream="<?= htmlspecialchars(STREAM_URL, ENT_QUOTES, 'UTF-8'); ?>"
      data-default-art="<?= htmlspecialchars(DEFAULT_ART, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="layout">
        <section class="cover-art" id="coverArt"
                 style="background-image: url('<?= htmlspecialchars($stats['albumArt'], ENT_QUOTES, 'UTF-8'); ?>');">
        </section>

        <section class="track-meta">
            <span class="badge">
                <span class="pulse-dot"></span>
                Canlı Yayın
            </span>
            <h1 id="trackTitle">
                <?= htmlspecialchars($stats['songTitle'], ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <h2 id="trackArtist">
                <?= htmlspecialchars($stats['artist'] !== '' ? $stats['artist'] : $stats['serverTitle'], ENT_QUOTES, 'UTF-8'); ?>
            </h2>
            <div class="controls">
                <button class="play-toggle" id="playToggle" aria-label="Yayını Başlat">
                    &#9658;
                </button>
                <button class="secondary" id="fullscreenToggle" aria-label="Tam ekran">
                    ⤢ Tam Ekran
                </button>
                <div class="live-indicator">
                    <span class="pulse-dot"></span>
                    Full HD Ses
                </div>
            </div>
            <div class="info-grid">
                <div class="info-card">
                    <span>Dinleyiciler</span>
                    <strong id="listenerCount"><?= htmlspecialchars((string) $stats['listeners'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="info-card">
                    <span>Radyo</span>
                    <strong id="stationName"><?= htmlspecialchars($stats['serverTitle'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>
        </section>
    </div>

    <audio id="radioAudio" preload="none" playsinline>
        <source src="<?= htmlspecialchars(STREAM_URL, ENT_QUOTES, 'UTF-8'); ?>" type="audio/mpeg">
        Tarayıcınız canlı yayını çalamıyor.
    </audio>
</main>

<script>
    const playerEl = document.getElementById('radioPlayer');
    const audioEl = document.getElementById('radioAudio');
    const playToggle = document.getElementById('playToggle');
    const fullscreenToggle = document.getElementById('fullscreenToggle');
    const trackTitleEl = document.getElementById('trackTitle');
    const trackArtistEl = document.getElementById('trackArtist');
    const listenerCountEl = document.getElementById('listenerCount');
    const stationNameEl = document.getElementById('stationName');
    const coverArtEl = document.getElementById('coverArt');

    const endpoint = playerEl.dataset.endpoint || '';
    const streamUrl = playerEl.dataset.stream || '';
    const defaultArt = playerEl.dataset.defaultArt || '';

    let isPlaying = false;
    let albumArtCache = {};
    let autoStartArmed = true;

    const updatePlayButton = () => {
        playToggle.innerHTML = isPlaying ? '&#10074;&#10074;' : '&#9658;';
        playToggle.setAttribute('aria-label', isPlaying ? 'Yayını Duraklat' : 'Yayını Başlat');
    };

    const requestAlbumArt = async (artist, track) => {
        const key = `${artist} - ${track}`;
        if (albumArtCache[key]) {
            return albumArtCache[key];
        }

        if (!artist && !track) {
            return defaultArt;
        }

        try {
            const query = encodeURIComponent([artist, track].filter(Boolean).join(' '));
            const response = await fetch(`https://itunes.apple.com/search?term=${query}&media=music&limit=1`);
            if (!response.ok) {
                throw new Error('Album art fetch failed');
            }
            const data = await response.json();
            if (data.results && data.results.length > 0 && data.results[0].artworkUrl100) {
                const artUrl = data.results[0].artworkUrl100.replace('100x100bb.jpg', '512x512bb.jpg');
                albumArtCache[key] = artUrl;
                return artUrl;
            }
        } catch (error) {
            console.warn('Albüm görseli alınamadı:', error);
        }

        albumArtCache[key] = defaultArt;
        return defaultArt;
    };

    const refreshStats = async () => {
        if (!endpoint) {
            return;
        }

        try {
            const response = await fetch(endpoint, { cache: 'no-store' });
            if (!response.ok) {
                throw new Error('İstek başarısız oldu');
            }
            const data = await response.json();

            trackTitleEl.textContent = data.songTitle || 'Canlı Yayın';
            trackArtistEl.textContent = data.artist || data.serverTitle || 'Çorlu FM';
            listenerCountEl.textContent = data.listeners ?? '0';
            stationNameEl.textContent = data.serverTitle || 'Çorlu FM';

            const artUrl = await requestAlbumArt(data.artist || '', data.track || data.songTitle || '');
            coverArtEl.style.backgroundImage = `url('${artUrl || defaultArt}')`;

        } catch (error) {
            console.warn('Statü güncellemesi başarısız:', error);
        }
    };

    const armAutoStartFallback = () => {
        if (!autoStartArmed) {
            return;
        }

        const resume = () => {
            document.removeEventListener('pointerdown', resume);
            document.removeEventListener('keydown', resume);
            document.removeEventListener('touchstart', resume);
            autoStartArmed = false;
            startStream({ force: true });
        };

        document.addEventListener('pointerdown', resume, { once: true });
        document.addEventListener('keydown', resume, { once: true });
        document.addEventListener('touchstart', resume, { once: true });
    };

    const startStream = async (options = {}) => {
        const { force = false, auto = false } = options;
        try {
            if (audioEl.paused || force) {
                if (!streamUrl) {
                    return;
                }

                if (!audioEl.src || force) {
                    audioEl.src = streamUrl;
                    audioEl.load();
                }

                const playPromise = audioEl.play();
                if (playPromise !== undefined) {
                    await playPromise;
                }

                autoStartArmed = false;
                isPlaying = true;
                updatePlayButton();
            }
        } catch (error) {
            console.warn('Yayın başlatılamadı:', error);
            if (auto) {
                armAutoStartFallback();
            }
        }
    };

    const stopStream = () => {
        audioEl.pause();
        audioEl.removeAttribute('src');
        audioEl.load();
        isPlaying = false;
        updatePlayButton();
    };

    playToggle.addEventListener('click', () => {
        if (isPlaying) {
            stopStream();
        } else {
            autoStartArmed = false;
            startStream({ force: true });
        }
    });

    fullscreenToggle.addEventListener('click', () => {
        playerEl.classList.toggle('fullscreen');
        fullscreenToggle.textContent = playerEl.classList.contains('fullscreen') ? '⤢ Çıkış' : '⤢ Tam Ekran';
    });

    audioEl.addEventListener('playing', () => {
        isPlaying = true;
        updatePlayButton();
    });

    audioEl.addEventListener('pause', () => {
        isPlaying = false;
        updatePlayButton();
    });

    updatePlayButton();
    refreshStats();
    setInterval(refreshStats, 10000);
    startStream({ auto: true });
</script>
</body>
</html>
