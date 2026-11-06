# SLink — Multi-User URL Shortener Platform

Platform **Multi-User URL Shortener & Click Analytics** berbasis PHP Native (PDO) dan MySQL/MariaDB dengan desain **Minimalist Monochrome + Red (`#E60012`)**.

---

## 1. Persyaratan Sistem & PHP Extensions

- **PHP**: `>= 8.1` (Direkomendasikan PHP `8.2` atau `8.3`)
- **Database**: MySQL `>= 8.0` atau MariaDB `>= 10.4`
- **Web Server**: Apache `2.4+` dengan `mod_rewrite` dan `mod_headers` aktif
- **PHP Extensions Wajib**:
  - `pdo` & `pdo_mysql` (Koneksi database dengan prepared statements)
  - `mbstring` (Manipulasi string UTF-8)
  - `json` (Serialisasi data analytics & Chart.js)
  - `openssl` / `random` (Pembuatan CSRF token & random short code kriptografis)
  - `session` (Manajemen sesi autentikasi)
  - `filter` (Validasi URL, IP, dan email)

---

## 2. Struktur Direktori

```text
/public
    index.php              # Front controller utama
    .htaccess              # Rewrite rules & HTTP security headers
    favicon.svg            # Brand icon
    /assets
        /css/app.css       # Monochrome + #E60012 design system
        /js/app.js         # Sidebar, Toast, Clipboard, QR Code generator, Modals

/app
    /Config
        app.php
        database.php
    /Core
        Auth.php           # Session auth, bcrypt verification, brute-force rate limiter
        Controller.php     # Base controller & error renderer
        Database.php       # Singleton PDO connection (emulate prepares disabled)
        Router.php         # Regex router + middleware pipeline
        Security.php       # CSRF, HTTP headers, URL scheme validator, alias validator
    /Controllers
        AuthController.php
        DashboardController.php
        ShortlinkController.php
        RedirectController.php
        AnalyticsController.php
        UserController.php
        SettingsController.php
    /Models
        User.php
        Shortlink.php
        Click.php
        ActivityLog.php
        Setting.php
    /Middleware
        AuthMiddleware.php
        RoleMiddleware.php
    /Helpers
        functions.php      # Output escaping e(), User-Agent & Bot parser, Geo resolver
    /Views
        /layouts           # app.php, guest.php
        /auth              # login.php
        /dashboard         # user.php, superuser.php
        /shortlinks        # index.php, form.php
        /analytics         # overview.php, detail.php
        /users             # index.php, detail.php, form.php, profile.php
        /settings          # index.php, logs.php
        /errors            # 403.php, 404.php, 500.php, expired.php, inactive.php

/config
    config.example.php     # Template konfigurasi environment
    config.php             # Konfigurasi aktif (jangan di-commit ke repositori publik)

/database
    schema.sql             # Struktur tabel, index, foreign key, & unique binary constraint
    seed.sql               # Data awal superuser, user, shortlinks, & sample click analytics

/storage
    /logs
        .htaccess          # Deny all direct HTTP access
        app.log            # Application error log
```

---

## 3. Instalasi Database & Konfigurasi

### A. Import Database
Jalankan `database/schema.sql` diikuti `database/seed.sql`:

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p slink_db < database/seed.sql
```

### B. Konfigurasi Database & Aplikasi
Salin `config/config.example.php` menjadi `config/config.php` dan sesuaikan kredensial:

```php
'database' => [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'slink_db',
    'username' => 'db_user',
    'password' => 'STRONG_DB_PASSWORD',
    'charset'  => 'utf8mb4',
],
```

---

## 4. Pembuatan / Reset Akun Superuser Pertama

Secara default `database/seed.sql` menyediakan akun awal berikut:

| Role | Email | Password Default |
|---|---|---|
| **Superuser** | `admin@s.domain.com` | `SLink#Admin!92xQv` |
| **User** | `nadia@s.domain.com` | `SLink#User!48mKp` |
| **User** | `rafi@s.domain.com` | `SLink#User!48mKp` |

Untuk membuat Superuser baru atau mengganti password melalui CLI di server:

```bash
php -r '
require "app/Core/Database.php";
require "app/Helpers/functions.php";
$pdo = App\Core\Database::connection();
$hash = password_hash("PasswordSuperKu#2026!", PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, \"superuser\", \"active\", NOW(), NOW())");
$stmt->execute(["Super Admin", "owner@s.domain.com", $hash]);
echo "Superuser created!\n";
'
```

---

## 5. Deployment Production (`https://s.domain.com`)

### Opsi 1 (Rekomendasi Utama — VPS / Cloud / cPanel Subdomain): DocumentRoot ke `/public`
Arahkan `DocumentRoot` domain `s.domain.com` langsung ke folder `/public`:

```apache
<VirtualHost *:443>
    ServerName s.domain.com
    DocumentRoot /var/www/slink/public

    <Directory /var/www/slink/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/s.domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/s.domain.com/privkey.pem
</VirtualHost>
```

### Opsi 2 (Shared Hosting Tanpa Kustomisasi DocumentRoot)
Jika shared hosting memaksa root direktori berada di `/public_html`:
1. Salin seluruh isi project ke `/public_html`.
2. File `.htaccess` di root project sudah dikonfigurasi untuk **memblokir akses HTTP langsung (`403 Forbidden`)** ke folder `/app`, `/config`, `/database`, `/storage`, serta file `.sql`, `.log`, dan `.md`, lalu meneruskan request secara internal ke `public/index.php`.

### Permission Folder Storage
Pastikan web server memiliki izin tulis pada `/storage/logs` dan tidak mengeksekusi script di dalamnya:

```bash
chmod 750 storage storage/logs
chmod 640 config/config.php
```

### Konfigurasi HTTPS & Shortlink Domain
1. Setelah login sebagai **Superuser**, buka menu **App Settings** (`/admin/settings`).
2. Isi **Shortlink Base Domain** dengan `https://s.domain.com`.
3. Pastikan `debug => false` pada `config/config.php` di environment production.

