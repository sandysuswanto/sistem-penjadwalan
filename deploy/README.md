# Panduan Deploy Laravel 13 (Non-Docker, DuckDNS, SSL)

Panduan ini berisi langkah-langkah untuk melakukan deployment aplikasi **sistem-penjadwalan** pada server VPS Linux (Ubuntu 22.04 / 24.04) menggunakan **Nginx**, **PHP 8.3-FPM**, **DuckDNS** (sebagai Dynamic DNS), dan **SSL Let's Encrypt**.

---

## 🛠️ Prasyarat (Prerequisites)

Pastikan server Anda terhubung ke internet dan memiliki akses internet publik. Kita akan menggunakan:
- **OS**: Ubuntu (rekomendasi: 22.04 LTS atau 24.04 LTS)
- **Web Server**: Nginx
- **PHP**: PHP 8.3 + extension yang dibutuhkan Laravel
- **Database**: SQLite (sesuai `.env.example`) atau MySQL/PostgreSQL
- **Process Manager**: Systemd (untuk Queue Worker)
- **SSL**: Certbot (Let's Encrypt)
- **DNS**: DuckDNS (Akun & Domain aktif)

---

## 📋 Langkah-Langkah Deployment

### Langkah 1: Setup DuckDNS (Dynamic DNS)

Jika server Anda menggunakan IP publik dinamis (misal internet rumah/IP VPS yang bisa berubah), DuckDNS berguna untuk mengarahkan domain Anda ke IP server secara otomatis.

1. Buka [DuckDNS](https://www.duckdns.org) dan buat subdomain (contoh: `penjadwalan-saya.duckdns.org`). Make sure you note your **Token**.
2. Salin script updater DuckDNS yang sudah disediakan ke server Anda:
   - File: `deploy/duckdns.sh`
3. Edit file `deploy/duckdns.sh` di server Anda dan sesuaikan isinya:
   ```bash
   SUBDOMAIN="penjadwalan-saya" # subdomain Anda
   TOKEN="token-duckdns-anda"
   ```
4. Berikan izin eksekusi pada script:
   ```bash
   chmod +x /var/www/sistem-penjadwalan/deploy/duckdns.sh
   ```
5. Jadwalkan di Cron agar IP ter-update otomatis setiap 5 menit:
   ```bash
   crontab -e
   ```
   Tambahkan baris berikut di paling bawah file:
   ```bash
   */5 * * * * /var/www/sistem-penjadwalan/deploy/duckdns.sh >/dev/null 2>&1
   ```
6. Jalankan sekali secara manual untuk memastikan IP awal ter-update:
   ```bash
   /var/www/sistem-penjadwalan/deploy/duckdns.sh
   cat /var/log/duckdns.log
   ```
   *(Pastikan output log menunjukkan `DuckDNS update OK`)*

---

### Langkah 2: Install Dependensi Server

Masuk ke VPS Anda via SSH, lalu jalankan perintah berikut untuk menginstal package yang diperlukan:

```bash
# Update repository
sudo apt update && sudo apt upgrade -y

# Install tools dasar
sudo apt install -y curl git unzip supervisor software-properties-common

# Tambahkan repository PHP jika belum ada
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 dan extension Laravel
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-tokenizer php8.3-intl php8.3-gd

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js & NPM (untuk compile asset frontend)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

### Langkah 3: Clone Aplikasi & Setup Direktori

1. Buat folder `/var/www/sistem-penjadwalan` dan clone project Anda ke sana:
   ```bash
   sudo mkdir -p /var/www/sistem-penjadwalan
   sudo chown -R $USER:www-data /var/www/sistem-penjadwalan
   git clone <URL_REPOSITORY_ANDA> /var/www/sistem-penjadwalan
   ```
2. Salin config `.env` dan konfigurasikan:
   ```bash
   cd /var/www/sistem-penjadwalan
   cp .env.example .env
   ```
3. Edit file `.env` (misal menggunakan `nano .env`):
   - Ubah `APP_ENV=production`
   - Ubah `APP_DEBUG=false`
   - Ubah `APP_URL=https://penjadwalan-saya.duckdns.org`
   - Sesuaikan konfigurasi database (jika menggunakan SQLite, biarkan default `DB_CONNECTION=sqlite` dan buat file databasenya: `touch database/database.sqlite`).
4. Berikan izin tulis untuk folder `storage` dan `bootstrap/cache` agar Nginx (user `www-data`) dapat menulis file:
   ```bash
   sudo chgrp -R www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

---

### Langkah 4: Dapatkan SSL Let's Encrypt (Certbot)

Gunakan Certbot untuk mendapatkan sertifikat SSL gratis untuk domain DuckDNS Anda.

1. Install Certbot:
   ```bash
   sudo apt install -y certbot python3-certbot-nginx
   ```
2. Pastikan port 80 pada router/firewall Anda terbuka ke internet. Jalankan Certbot untuk membuat sertifikat SSL:
   ```bash
   sudo certbot certonly --nginx -d penjadwalan-saya.duckdns.org
   ```
   *(Pilih opsi webroot atau standalone sesuai petunjuk Certbot. Let's Encrypt akan memverifikasi kepemilikan domain).*
   
   Sertifikat SSL Anda akan disimpan di `/etc/letsencrypt/live/penjadwalan-saya.duckdns.org/`.

---

### Langkah 5: Konfigurasi Nginx Web Server

1. Salin konfigurasi Nginx dari project ke folder Nginx:
   ```bash
   sudo cp deploy/nginx.conf /etc/nginx/sites-available/sistem-penjadwalan
   ```
2. Edit file konfigurasi tersebut:
   ```bash
   sudo nano /etc/nginx/sites-available/sistem-penjadwalan
   ```
   **Ubah hal-hal berikut:**
   - Ganti `yourdomain.duckdns.org` dengan subdomain DuckDNS asli Anda (misal `penjadwalan-saya.duckdns.org`). Ada beberapa tempat, pastikan diubah semua (termasuk path sertifikat SSL).
   - Pastikan path `root /var/www/sistem-penjadwalan/public;` sudah benar.
3. Aktifkan konfigurasi dengan membuat symlink ke `sites-enabled`:
   ```bash
   sudo ln -s /etc/nginx/sites-available/sistem-penjadwalan /etc/nginx/sites-enabled/
   ```
4. Hapus konfigurasi default Nginx agar tidak bentrok:
   ```bash
   sudo rm /etc/nginx/sites-enabled/default
   ```
5. Tes konfigurasi Nginx dan restart:
   ```bash
   sudo nginx -t
   sudo systemctl restart nginx
   ```

---

### Langkah 6: Konfigurasi Laravel Queue Worker (Systemd)

Untuk menjalankan background job secara terus menerus (seperti antrean email, event scheduler, dll):

1. Salin file service systemd ke direktori systemd:
   ```bash
   sudo cp deploy/laravel-worker.service /etc/systemd/system/laravel-worker.service
   ```
2. Reload systemd agar membaca service baru:
   ```bash
   sudo systemctl daemon-reload
   ```
3. Aktifkan dan jalankan service secara otomatis saat boot:
   ```bash
   sudo systemctl enable --now laravel-worker.service
   ```
4. Periksa status service untuk memastikan berjalan lancar:
   ```bash
   sudo systemctl status laravel-worker.service
   ```

---

### Langkah 7: Otomatisasi Deploy dengan `deploy.sh`

Di server, Anda dapat memperbarui kode dan men-deploy-nya dengan sekali perintah menggunakan script `deploy.sh`.

1. Agar user Anda (atau user deployment) dapat me-restart PHP-FPM dan Worker tanpa dimintai password sudo, tambahkan izin sudoers:
   ```bash
   sudo visudo
   ```
   Tambahkan baris berikut di akhir file:
   ```text
   # Izinkan user Anda (atau www-data) merestart php-fpm dan queue worker tanpa password
   www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm, /usr/bin/systemctl restart laravel-worker.service
   # (Jika Anda login sebagai user non-root lain, misal 'ubuntu', ganti 'www-data' dengan username Anda)
   ubuntu ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm, /usr/bin/systemctl restart laravel-worker.service
   ```
2. Jalankan setup awal aplikasi secara manual (hanya pertama kali):
   ```bash
   cd /var/www/sistem-penjadwalan
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   # Buat database sqlite jika belum ada
   touch database/database.sqlite
   php artisan migrate --force
   npm install
   npm run build
   ```
3. Berikan hak eksekusi pada script deploy:
   ```bash
   chmod +x deploy/deploy.sh
   ```
4. Kapan pun ada update baru di Git, Anda cukup menjalankan script ini untuk update otomatis:
   ```bash
   ./deploy/deploy.sh
   ```
