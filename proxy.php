<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$xmlUrl = 'https://sssx.radyosfer.com/corlufm/stats?sid=1';

try {
    // XML'i çek
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $xmlContent = file_get_contents($xmlUrl, false, $context);
    
    if ($xmlContent === false) {
        throw new Exception('XML içeriği alınamadı');
    }
    
    // XML'i parse et
    $xml = simplexml_load_string($xmlContent);
    
    if ($xml === false) {
        throw new Exception('XML parse edilemedi');
    }
    
    // Verileri çıkar
    $songTitle = (string)$xml->SONGTITLE ?? 'Canlı Yayın';
    $serverTitle = (string)$xml->SERVERTITLE ?? 'Çorlu FM';
    $listeners = (int)$xml->CURRENTLISTENERS ?? 0;
    $streamStatus = (string)$xml->STREAMSTATUS ?? '0';
    
    // Şarkı ve sanatçıyı ayır
    $artist = '';
    $track = '';
    
    if (strpos($songTitle, ' - ') !== false) {
        $parts = explode(' - ', $songTitle, 2);
        $artist = trim($parts[0]);
        $track = trim($parts[1]);
    } else {
        $track = $songTitle;
    }
    
    // JSON yanıtı hazırla
    $response = [
        'success' => true,
        'data' => [
            'songTitle' => $track ?: $songTitle,
            'artist' => $artist ?: $serverTitle,
            'serverTitle' => $serverTitle,
            'listeners' => $listeners,
            'isOnline' => $streamStatus === '1',
            'rawSong' => $songTitle
        ],
        'timestamp' => time()
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Hata durumunda varsayılan değerler
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [
            'songTitle' => 'Canlı Yayın',
            'artist' => 'Çorlu FM',
            'serverTitle' => 'Çorlu FM',
            'listeners' => 0,
            'isOnline' => true,
            'rawSong' => 'Canlı Yayın'
        ],
        'timestamp' => time()
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
