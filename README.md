# CRUD Mahasiswa

Aplikasi web sederhana berbasis **Laravel 10** dengan arsitektur **monolith (MVC)**. Routing, logika aplikasi, akses database, dan tampilan Blade berada dalam satu proyek. Semua formulir dikirim ke server menggunakan HTTP biasa.

## Fitur

- CRUD Prodi: tambah, tampilkan, ubah, dan hapus data `id` dan `nama_prodi`.
- CRUD Mahasiswa: tambah, tampilkan, ubah, dan hapus data `id`, `id_prodi`, `nama`, dan `nim`.
- Setiap mahasiswa wajib terhubung ke tepat satu prodi; satu prodi dapat memiliki banyak mahasiswa.
- NIM unik dan disimpan sebagai teks agar angka nol di awal tetap terjaga.
- Prodi yang masih memiliki mahasiswa tidak dapat dihapus. Pindahkan atau hapus mahasiswanya terlebih dahulu.
- Navbar, tabel responsif, formulir, pesan validasi, dan pagination Bootstrap 5 (10 data per halaman).
- Konfirmasi hapus melalui halaman tersendiri, tanpa JavaScript, jQuery, DataTables, atau AJAX.
- CSS Bootstrap 5.3.8 tersedia lokal di `public/css/bootstrap.min.css`; tampilan tidak membutuhkan koneksi CDN.

Aplikasi ini tidak menyediakan login. Halaman awal mengarah ke daftar mahasiswa.

## Persyaratan

- PHP **8.2** untuk dependensi dalam `composer.lock` proyek ini.
- Composer 2 dan Git.
- MySQL 5.7+ atau MariaDB yang kompatibel, dijalankan melalui Laragon.
- Ekstensi PHP Laravel: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, dan XML; aktifkan juga `pdo_mysql` dan Zip.

Node.js, NPM, dan proses build Vite tidak diperlukan karena view langsung memakai CSS Bootstrap lokal.

## Instalasi setelah clone dari GitHub

Jalankan perintah dari terminal di folder proyek. Pastikan akun GitHub Anda memiliki akses ke repositori.

### 1. Clone repositori

```bash
git clone https://github.com/yuliant/crud_mahasiswa_monolith.git
cd crud_mahasiswa_monolith
```

### 2. Instal dependensi PHP

```bash
composer install
```

Gunakan `composer install` agar versi dependensi mengikuti `composer.lock` yang disertakan dalam repositori.

### 3. Buat file konfigurasi

Linux/macOS/Git Bash:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Buat application key:

```bash
php artisan key:generate
```

### 4. Siapkan database MySQL/MariaDB

Buka Laragon dan aktifkan layanan MySQL (tombol **Start All** atau menu **MySQL → Start**), lalu buat database melalui phpMyAdmin, HeidiSQL, atau terminal SQL:

```sql
CREATE DATABASE crud_mahasiswa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sesuaikan konfigurasi `.env` dengan akun database di komputer Anda:

```dotenv
APP_NAME="CRUD Mahasiswa"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crud_mahasiswa
DB_USERNAME=root
DB_PASSWORD=
```

Isikan `DB_PASSWORD` jika akun database memakai password. Setelah mengubah konfigurasi, jalankan:

```bash
php artisan config:clear
```

### 5. Jalankan migration

```bash
php artisan migrate
```

Migration membuat tabel `prodi` dan `mahasiswa` beserta foreign key. ID dibuat otomatis oleh database. Kedua tabel hanya memiliki kolom data yang disebutkan di atas, tanpa timestamps.

Opsional, tambahkan dua prodi dan dua mahasiswa contoh:

```bash
php artisan db:seed
```

Seeder dapat dijalankan ulang tanpa menggandakan data contoh yang sama.

### 6. Jalankan aplikasi

```bash
php artisan serve
```

Buka [http://127.0.0.1:8000](http://127.0.0.1:8000). Tambah prodi terlebih dahulu, kemudian tambah mahasiswa dan pilih prodinya.

Jika memakai virtual host Laragon/Apache/Nginx, arahkan document root ke folder `public`, kemudian sesuaikan `APP_URL`. Folder `storage` dan `bootstrap/cache` harus dapat ditulis oleh PHP.

## Struktur monolith

```text
app/
  Http/Controllers/
    ProdiController.php          # CRUD dan validasi prodi
    MahasiswaController.php      # CRUD dan validasi mahasiswa
  Models/
    Prodi.php                    # Relasi hasMany mahasiswa
    Mahasiswa.php                # Relasi belongsTo prodi
routes/web.php                   # Seluruh route aplikasi
resources/views/
  layouts/app.blade.php          # Layout bersama dan navbar
  prodi/                        # Tabel serta form tambah/edit prodi
  mahasiswa/                    # Tabel serta form tambah/edit mahasiswa
  confirm-delete.blade.php      # Halaman konfirmasi hapus
database/migrations/             # Skema tabel dan foreign key
database/seeders/DatabaseSeeder.php
public/css/bootstrap.min.css     # Bootstrap lokal
tests/Feature/CrudTest.php        # Pengujian aplikasi
```

Alur permintaan: **Browser → Route → Controller → Model/Database → View Blade → Browser**. Aplikasi tidak memerlukan frontend atau layanan API terpisah.

## Struktur database

| Tabel | Kolom | Aturan |
| --- | --- | --- |
| `prodi` | `id` | Primary key, auto increment |
| `prodi` | `nama_prodi` | Wajib, maksimal 100 karakter |
| `mahasiswa` | `id` | Primary key, auto increment |
| `mahasiswa` | `id_prodi` | Wajib, foreign key ke `prodi.id`, restrict on delete |
| `mahasiswa` | `nama` | Wajib, maksimal 100 karakter |
| `mahasiswa` | `nim` | Wajib, unik, maksimal 30 karakter |

## Route aplikasi

Pola route berikut berlaku untuk masing-masing resource `prodi` dan `mahasiswa`:

| Method | URL | Kegunaan |
| --- | --- | --- |
| GET | `/` | Redirect ke `/mahasiswa` |
| GET | `/{resource}` | Menampilkan tabel |
| GET | `/{resource}/create` | Form tambah |
| POST | `/{resource}` | Menyimpan data baru |
| GET | `/{resource}/{id}/edit` | Form edit |
| PUT/PATCH | `/{resource}/{id}` | Menyimpan perubahan |
| GET | `/{resource}/{id}/hapus` | Konfirmasi hapus, tidak menghapus data |
| DELETE | `/{resource}/{id}` | Menghapus data |

Form memakai proteksi CSRF Laravel. Method PUT dan DELETE dikirim melalui method spoofing Blade.

## Pengujian

Pengujian bersifat opsional dan menggunakan **MySQL Laragon**, dengan database terpisah agar data aplikasi tidak terhapus. Database pengujian tidak dibuat saat instalasi aplikasi. Jika ingin menjalankan tes, buat terlebih dahulu:

```sql
CREATE DATABASE crud_mahasiswa_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Pastikan MySQL Laragon aktif dan kredensial di `.env` sudah sesuai, lalu jalankan:

```bash
php artisan config:clear
php artisan test
```

`phpunit.xml` menetapkan koneksi `mysql` dan database `crud_mahasiswa_testing`. Host, port, username, dan password mengikuti `.env`. Database pengujian ini khusus untuk tes; isinya dibangun ulang oleh `RefreshDatabase`. Jangan menyimpan data aplikasi di dalamnya.

Setelah selesai menjalankan tes, database pengujian dapat dihapus:

```sql
DROP DATABASE crud_mahasiswa_testing;
```

Cakupan pengujian meliputi CRUD, validasi, NIM unik, perubahan prodi, proteksi foreign key, konfirmasi hapus, pagination, escaping HTML, dan seeder.

## Kendala umum

- **`vendor/autoload.php` tidak ditemukan:** jalankan `composer install`.
- **`No application encryption key has been specified`:** jalankan `php artisan key:generate`.
- **`Unknown database` atau `Access denied`:** buat database dan periksa kredensial `.env`.
- **`Connection refused`:** pastikan server MySQL aktif dan port sesuai `.env`.
- **`could not find driver`:** aktifkan `pdo_mysql`; periksa PHP CLI dengan `php --ini` dan `php -m`.
- **Tabel belum tersedia:** jalankan `php artisan migrate`.
- **Perubahan `.env` belum terbaca:** jalankan `php artisan config:clear`.
- **Port 8000 terpakai:** jalankan `php artisan serve --port=8001`, lalu buka port tersebut.

File `.env` dan folder `vendor` tidak disimpan ke Git. Sertakan `.env.example`, migration, seeder, `composer.json`, `composer.lock`, dan CSS Bootstrap saat mengunggah source ke GitHub.

## Referensi

- [Dokumentasi Laravel 10](https://laravel.com/docs/10.x)
- [Validasi Laravel 10](https://laravel.com/docs/10.x/validation)
- [Dokumentasi Bootstrap 5.3](https://getbootstrap.com/docs/5.3/getting-started/introduction/)

