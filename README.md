# Çorlu FM - Canlı Radyo Player

Modern, responsive ve tam özellikli canlı radyo player uygulaması.

## Özellikler

- 🎵 **Canlı Yayın**: Gerçek zamanlı radyo yayını
- 🎨 **Modern Tasarım**: Spotify tarzı karanlık/açık tema
- 🌓 **Dark/Light Mode**: Otomatik tema değiştirici
- 📱 **Mobile Optimized**: Tam mobil optimize
- 🔊 **Ses Kontrolü**: SVG ikonlu mute ve ses seviyesi
- 📊 **Dinleyici Bilgisi**: Anlık ve en çok dinleyici sayısı
- 🎨 **Albüm Kapağı**: iTunes API ile otomatik albüm kapağı
- 🎯 **ON AIR Badge**: Kırmızı, kalın, parlayan badge
- 📺 **Stereo Indicator**: Dinamik kırmızı/standart renk
- 🔧 **Reklam Temizleme**: Otomatik ID temizleme
- ⌨️ **Kısayollar**: Space (Play/Pause), F (Fullscreen), M (Mute)
- 🌐 **Akıllı Deployment**: Localhost ve Production otomatik algılama

## Dosyalar

- `index.html` - GitHub Pages wrapper (akıllı URL seçimi ile PHP dosyasını çağırır)
- `canli-yayin.php` - Tek dosya player (PHP backend + frontend)

## Kullanım

### PHP Sunucu ile (Önerilen)

1. `canli-yayin.php` dosyasını PHP destekleyen sunucuya yükleyin
2. `index.html` otomatik olarak localhost veya production sunucusunu algılar
3. `index.html` dosyasını GitHub Pages'de yayınlayın

**Akıllı URL Seçimi:**
- **Localhost'ta:** `index.html` → `localhost:8000/canli-yayin.php`
- **GitHub Pages'de:** `index.html` → `kolaypanel.com/canli-yayin.php`

Örnek deployment:
- PHP dosyası: `https://kolaypanel.com/canli-yayin.php`
- GitHub Pages: `https://yourusername.github.io/corlu-fm-player`

### Sadece PHP Sunucu

1. `canli-yayin.php` dosyasını sunucuya yükleyin
2. Doğrudan tarayıcıdan açın

## Son Güncellemeler

### v2.2.0 - Reklam Temizleme & SVG İkonlar
- ✅ Reklam ID'leri otomatik temizleniyor (parantez içi sayılar kaldırılıyor)
- ✅ SVG ikonlar (emoji yerine profesyonel görünüm)
- ✅ Akıllı URL seçimi (localhost/production otomatik algılama)
- ✅ Title Case uygulama (Türkçe karakter desteği)
- ✅ Bağlaçlar küçük kalıyor ("ve", "ile", vb.)

### v2.1.0 - Yeni Özellikler
- ✅ "ON AIR" badge (kırmızı, kalın)
- ✅ Dinleyici sayıları (Anlık ve En Çok Dinleyici)
- ✅ Footer (Network.com.tr logo)
- ✅ Stereo indicator (dinamik kırmızı renk)

### v2.0.0 - Mobil Optimize & PHP Backend
- ✅ Dark/Light mode tema değiştirici
- ✅ Mobil için optimize edildi
- ✅ PHP backend ile albüm kapağı desteği
- ✅ Tek dosya player (canli-yayin.php)
- ✅ GitHub Pages + PHP sunucu hibrit deployment

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

- **Backend:** PHP 8+ (XML parsing, iTunes API, title case)
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **APIs:** iTunes Search API (albüm kapağı)
- **Audio:** HTML5 Audio Element
- **Features:** Dark/Light mode, Responsive design, SVG icons
- **Deployment:** GitHub Pages + PHP Host (hibrit sistem)

## Lisans

MIT License

