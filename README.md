# Çorlu FM - Canlı Radyo Player

Modern, responsive ve tam özellikli canlı radyo player uygulaması.

## Özellikler

- 🎵 **Canlı Yayın**: Gerçek zamanlı radyo yayını
- 🎨 **Modern Tasarım**: Spotify tarzı karanlık tema
- 🌓 **Dark/Light Mode**: Tema değiştirici
- 📱 **Mobile Optimized**: Mobil için optimize edilmiş
- 🔊 **Ses Kontrolü**: Mute ve ses seviyesi kontrolü
- 📊 **Dinleyici Bilgisi**: Anlık dinleyici sayısı
- 🎨 **Albüm Kapağı**: iTunes API ile otomatik albüm kapağı
- ⌨️ **Kısayollar**: Space (Play/Pause), F (Fullscreen), M (Mute)

## Dosyalar

- `index.html` - GitHub Pages wrapper (iframe ile PHP dosyasını çağırır)
- `canli-yayin.php` - Tek dosya player (PHP backend + frontend)
- `player.html` - Standalone HTML player (eski)

## Kullanım

### PHP Sunucu ile (Önerilen)

1. `canli-yayin.php` dosyasını PHP destekleyen sunucuya yükleyin
2. `index.html` dosyasını düzenleyin ve iframe src'yi PHP sunucunuzun URL'sine ayarlayın
3. `index.html` dosyasını GitHub Pages'de yayınlayın

Örnek deployment:
- PHP dosyası: `https://yourphphost.com/corlu-fm-player/canli-yayin.php`
- GitHub Pages: `https://yourusername.github.io/corlu-fm-player`

### Sadece PHP Sunucu

1. `canli-yayin.php` dosyasını sunucuya yükleyin
2. Doğrudan tarayıcıdan açın

## Son Güncellemeler

### v2.0.0 - Mobil Optimize & PHP Backend
- ✅ Dark/Light mode tema değiştirici eklendi
- ✅ Mobil için optimize edildi (touch-friendly controls)
- ✅ PHP backend ile albüm kapağı desteği
- ✅ Tek dosya player (canli-yayin.php)
- ✅ GitHub Pages + PHP sunucu hibrit deployment

### v1.1.0
- ✅ XML verilerini GitHub Pages'de çalışacak şekilde CORS proxy ile güncelleme
- ✅ Mobil görünümde "Canlı Yayın" badge'i ortalandı
- ✅ Alternatif CORS proxy'ler eklendi (fallback sistemi)
- ✅ Responsive tasarım iyileştirildi

## Deployment Örnekleri

### Option 1: GitHub Pages + PHP Sunucu

**PHP Sunucu (örn: kolaypanel.com)**
```
https://kolaypanel.com/corlu-fm/canli-yayin.php
```

**GitHub Pages**
```
https://yourusername.github.io/corlu-fm-player/
```

index.html iframe ile PHP dosyasını çağırır.

### Option 2: Sadece PHP Sunucu

`canli-yayin.php` dosyasını doğrudan PHP sunucuda barındırın.

## Teknolojiler

- PHP 8+ (Backend)
- HTML5
- CSS3
- Vanilla JavaScript
- iTunes Search API
- Audio Element (Web Audio API)
- XML Parsing

## Lisans

MIT License

