# Çorlu FM - Canlı Radyo Player

Modern, responsive ve tam özellikli canlı radyo player uygulaması.

## Özellikler

- Canlı yayın ve gerçek zamanlı radyo oynatıcı
- Dark/Light mode tema değiştirici
- Mobil için tam optimize
- SVG ikonlu ses kontrolü
- Dinleyici bilgileri (Anlık ve En Çok)
- iTunes API ile otomatik albüm kapağı
- ON AIR badge (kırmızı, kalın)
- Dinamik Stereo indicator
- Otomatik reklam ID temizleme
- Klavye kısayolları (Space, F, M)

## Dosyalar

- `canli-yayin.php` - Ana player dosyası (PHP backend + frontend)
- `index.html` - GitHub Pages wrapper

## Kullanım

1. `canli-yayin.php` dosyasını PHP destekleyen sunucuya yükleyin
2. `index.html` dosyasını GitHub Pages'de yayınlayın
3. Player otomatik olarak localhost veya production sunucusunu algılar

## Son Güncelleme

**v2.2.0** - Şubat 2025
- Reklam ID'leri otomatik temizleme
- SVG ikonlar
- Title Case uygulama (Türkçe karakter desteği)
- Bağlaçlar küçük kalıyor ("ve", "ile")
- Dinleyici sayıları (Anlık ve En Çok)
- ON AIR badge ve Stereo indicator
- Akıllı URL seçimi (localhost/production)

## Teknolojiler

- PHP 8+ (XML parsing, iTunes API)
- HTML5, CSS3, Vanilla JavaScript
- iTunes Search API
- HTML5 Audio Element

## Lisans

MIT License

