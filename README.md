docker-compose up -d
git add .
git commit -m "Initial commit"
git remote add origin <your-remote-url>
git branch -M main
git push -u origin main
docker-compose up -d
# bilet-satin-alma — ECA ticket sales (PHP + SQLite)

Bilet satın alma platformu (ECA görevi) — PHP ile yazılmış, SQLite veritabanı kullanan, Docker ile çalıştırılabilen bir örnek uygulama.

Bu repo PHP uygulamasının (public/, src/, views/) atölye uygulamasını içerir. Ayrıca orijinal Flask/py dosyaları referans amaçlı olarak korunmuştur.

Özellikler:

- PHP (plain) uygulama ve SQLite backend.
- Dockerfile ve docker-compose ile yerelde kolay çalışma.
- Kayıt/giriş (CSRF korumalı), koltuk seçimi UI (radio+label), cinsiyete göre renklendirme, bilet PDF üretimi (Dompdf).
- `bin/` altında idempotent migration ve yardımcı scriptler (init_db, migrate_all vb.).
- Basit PHPUnit ve küçük bir entegrasyon testi (CSRF -> login -> quickbuy).

Güvenlik notları:

- Yerelde bir `.env` dosyası ile güçlü bir SECRET_KEY kullanın (hiçbir zaman .env'i commit etmeyin).
- `instance/` klasörünü ve hassas dosyaları repoya eklemeyin; `.gitignore` içerir.

Hızlı başlatma (Docker önerilir):

```powershell
# build and run with docker-compose
docker-compose build --no-cache
docker-compose up -d
# open http://localhost:8000

# initialize SQLite DB inside instance folder
php bin/init_db.php
```

Yerel (Docker'sız) hızlı başlatma:

```powershell
php -S 127.0.0.1:8000 -t public
# sonra http://127.0.0.1:8000 adresini açın
```

İlk adımlar:

1. PHP bağımlılıklarını kurun (yerelde):

```powershell
composer install
```

2. Veritabanını başlatın ve yönetici oluşturun:

```powershell
php bin/init_db.php
php bin/create_admin.php
```

3. Eğer Docker kullanıyorsanız:

```powershell
docker-compose up -d
```

PDF oluşturma yardımcısı
-----------------------
Booking için PDF üretip kaydetme aracı bulunmaktadır (container içinde veya lokal olarak Dompdf kurulu ise):

```powershell
# container içinden booking id 1 için PDF üret ve kaydet
docker-compose exec web php /var/www/html/bin/generate_pdf_save.php 1

# container içinden kaydedilen dosyayı host'a kopyala
docker cp $(docker-compose ps -q web):/tmp/ticket_1.pdf .
```

Lint & CI
---------
Bir GitHub Actions workflow'u `.github/workflows/ci.yml` içinde yer alır; ayrıca temel PHP sözdizimi kontrolleri için bir `php-lint` workflow'u mevcuttur.

Yerelde tüm PHP dosyalarını kontrol etmek için (container içinde):

```powershell
docker-compose exec web bash -lc "find /var/www/html -type f -name '*.php' -print0 | xargs -0 -n1 php -l"
```

Entegrasyon testi (CSRF + quickbuy)
---------------------------------
Küçük bir entegrasyon testi `tests/integration/test_booking_flow.php` içinde yer alır. Test, CSRF token alma -> login -> quickbuy akışını doğrular. Test çalışırsa `OK quickBuy seat=...` çıktısı verir.

Genel notlar
-----------
- `bin/migrate_all.php` ile idempotent migrasyonları çalıştırabilirsiniz.
- Geçici debug POST kayıtları `src/controllers/BookingController.php` içinden kaldırıldı; yalnızca lokal debug için yeniden açın ve commit etmeyin.

Gereksinimler / Kurulum
----------------------
- PHP 8+, Composer (bağımlılıklar için), Docker (opsiyonel), ve SQLite.

Eğer GitHub'a push konusunda yardıma ihtiyacınız olursa (PAT, remote ayarları, credential temizleme vb.) adım adım yardımcı olabilirim.

