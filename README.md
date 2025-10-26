# Çorlu FM - Canlı Radyo Player

Modern, responsive ve tam özellikli canlı radyo player uygulaması.

## Özellikler

- 🎵 **Canlı Yayın**: Gerçek zamanlı radyo yayını
- 🎨 **Modern Tasarım**: Spotify tarzı karanlık tema
- 📱 **Responsive**: Tüm cihazlarda mükemmel görünüm
- 🔊 **Ses Kontrolü**: Mute ve ses seviyesi kontrolü
- 📊 **Dinleyici Bilgisi**: Anlık dinleyici sayısı
- 🎨 **Albüm Kapağı**: iTunes API ile otomatik albüm kapağı
- ⌨️ **Kısayollar**: Space (Play/Pause), F (Fullscreen), M (Mute)

## Dosyalar

- `index.html` - Ana sayfa (iframe wrapper)
- `player.html` - Radyo player
- `canli-yayin.php` - PHP backend (istatistik verileri)

## Kullanım

### GitHub Pages

1. Bu repo'yu GitHub'a push edin
2. Repository Settings > Pages bölümünden GitHub Pages'i etkinleştirin
3. Site otomatik olarak yayınlanır

### PHP Hosting

PHP kullanıyorsanız `canli-yayin.php` dosyasını kullanın.

## Son Güncellemeler

### v1.1.0
- ✅ XML verilerini GitHub Pages'de çalışacak şekilde CORS proxy ile güncelleme
- ✅ Mobil görünümde "Canlı Yayın" badge'i ortalandı
- ✅ Alternatif CORS proxy'ler eklendi (fallback sistemi)
- ✅ Responsive tasarım iyileştirildi

## Teknolojiler

- HTML5
- CSS3
- Vanilla JavaScript
- iTunes Search API
- Audio Element (Web Audio API)

## Lisans

MIT License

