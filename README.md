# Çorlu FM Canlı Yayın Player

Modern ve responsive tasarıma sahip Çorlu FM canlı yayın player'ı.

## Özellikler

- 🎵 Canlı radyo yayını
- 📊 Gerçek zamanlı dinleyici sayısı
- 🎨 Modern ve responsive tasarım
- 🔄 Otomatik şarkı bilgisi güncelleme (10 saniye)
- 📱 Mobil uyumlu
- 🌙 Dark mode desteği

## Teknik Detaylar

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP proxy (XML to JSON conversion)
- **Stream**: MP3 audio stream
- **API**: Shoutcast XML stats

## Kullanım

1. `index.html` dosyasını web sunucusunda çalıştırın
2. `proxy.php` dosyasının da aynı dizinde olduğundan emin olun
3. PHP desteği olan bir sunucuda çalıştırın

## Geliştirme

```bash
# Yerel sunucu başlatma
php -S localhost:8000

# Tarayıcıda açma
open http://localhost:8000
```

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır.
