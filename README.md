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

**v2.3.0** - Şubat 2025
- ON AIR badge yenilendi (koyu bg, beyaz border, oynatma sırasında kırmızı)
- STEREO büyük harf, playing'de kırmızı indicator
- Mobilde play-toggle daha büyük (70px)
- Şarkı/sanatçı font kalınlığı artırıldı
- Footer düzeni güncellendi (Çorlu FM + The Network logoları)
- Footer metinleri 2 satır formatı
- Info-card hizalama sorunu düzeltildi

## Teknolojiler

- PHP 8+ (XML parsing, iTunes API)
- HTML5, CSS3, Vanilla JavaScript
- iTunes Search API
- HTML5 Audio Element

## Lisans

MIT License

