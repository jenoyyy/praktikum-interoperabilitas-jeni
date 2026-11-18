# Dokumentasi OpenAPI — Praktikum Interoperabilitas

Dokumen ini menjelaskan kontrak API final untuk aplikasi Blog API. Spesifikasi lengkap tersedia pada file `openapi.json` dan mengikuti OpenAPI 3.0.3.

## 1. Menjalankan aplikasi Laravel

Semua endpoint Laravel berada di bawah prefix /api dan menggunakan base URL http://127.0.0.1:8000/api.

### Menggunakan Docker Desktop di Windows

Metode ini tidak membutuhkan PHP, Composer, Herd, atau ServBay yang terpasang di Windows.

1. Buka Docker Desktop dan tunggu sampai Docker Engine berstatus **Running**.
2. Dari PowerShell pada folder utama repository, jalankan:

~~~powershell
powershell -ExecutionPolicy Bypass -File .\scripts\start-laravel.ps1
~~~

Script akan membangun image PHP 8.4, menyiapkan file .env dan database SQLite, memasang dependency Composer, membuat application key bila belum ada, menjalankan migration, dan menyalakan server pada port 8000.

Untuk menghentikan server:

~~~powershell
powershell -ExecutionPolicy Bypass -File .\scripts\stop-laravel.ps1
~~~

### Menggunakan PHP lokal

Jika PHP 8.3+ dan Composer 2 sudah tersedia:

~~~bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
~~~

Jika memakai SQLite dan file database belum tersedia, buat file database/database.sqlite terlebih dahulu dan pastikan DB_CONNECTION=sqlite pada .env. Untuk MySQL/MariaDB, isi host, port, nama database, username, dan password sesuai konfigurasi lokal.

## 2. Daftar endpoint

| Method | Endpoint | Fungsi | Authentication / Ability |
| --- | --- | --- | --- |
| POST | `/api/register` | Mendaftarkan user baru | Publik |
| POST | `/api/login` | Login dan membuat token | Publik |
| GET | `/api/profile` | Melihat profil user aktif | Sanctum |
| POST | `/api/logout` | Mencabut token aktif | Sanctum |
| GET | `/api/blogs` | B — Browse semua blog | `blogs:read` |
| GET | `/api/blog/{id}` | R — Read detail blog | `blogs:read` + pemilik |
| PUT | `/api/blog/{id}` | E — Edit blog | `blogs:update` + pemilik |
| POST | `/api/blogs` | A — Add blog | `blogs:create` |
| DELETE | `/api/blog/{id}` | D — Delete blog | `blogs:delete` + pemilik |

Semua endpoint blog membutuhkan token Sanctum. Untuk detail, edit, dan hapus, sistem juga memeriksa bahwa blog dimiliki oleh user yang sedang login. Saat membuat blog, `user_id` diambil dari token aktif; mengirim `user_id` sendiri pada body tidak akan mengubah pemilik.

## 3. Mode token dan abilities

Kirim field `mode` pada request login untuk memilih kumpulan abilities praktikum. Jika tidak dikirim, mode default adalah `read`.

| Mode | Abilities yang diberikan |
| --- | --- |
| `read` | `blogs:read` |
| `edit` | `blogs:read`, `blogs:update` |
| `delete` | `blogs:read`, `blogs:delete` |
| `manage` | `blogs:read`, `blogs:update`, `blogs:create`, `blogs:delete` |

Contoh login:

~~~http
POST /api/login
Content-Type: application/json

{
  "email": "jeni@example.com",
  "password": "password123",
  "mode": "manage"
}
~~~

Simpan nilai `token` dari response. Untuk endpoint yang dilindungi, kirim header:

~~~http
Authorization: Bearer <TOKEN>
Accept: application/json
~~~

Mode dipakai untuk demonstrasi kemampuan token dalam praktikum. Pada aplikasi produksi, kemampuan token sebaiknya ditentukan berdasarkan izin yang diberikan server, bukan pilihan bebas dari client.

## 4. Contoh request BREAD

### Browse — GET /api/blogs

~~~bash
curl -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>" http://127.0.0.1:8000/api/blogs
~~~

### Read — GET /api/blog/1

~~~bash
curl -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>" http://127.0.0.1:8000/api/blog/1
~~~

### Add — POST /api/blogs

~~~json
{
  "title": "Belajar Laravel",
  "content": "Membuat REST API dengan Laravel dan Sanctum."
}
~~~

### Edit — PUT /api/blog/1

~~~json
{
  "title": "Belajar Laravel API",
  "content": "Konten yang sudah diperbarui."
}
~~~

### Delete — DELETE /api/blog/1

Tidak memerlukan request body. Gunakan token dengan ability `blogs:delete`.

## 5. Mencoba dokumentasi OpenAPI

File `openapi.json` dapat dibuka melalui Swagger Editor atau diimpor ke Insomnia/Postman.

Untuk menjalankan mock server menggunakan Prism (memerlukan Node.js/npm):

~~~bash
npx @stoplight/prism-cli mock openapi.json -p 4040
~~~

Kemudian coba endpoint mock, misalnya `http://127.0.0.1:4040/blogs`. Mock server hanya menggunakan contoh response pada kontrak OpenAPI; ia tidak menyimpan data, tidak memvalidasi token sungguhan, dan tidak menggantikan pengujian ke server Laravel.

Laravel Scramble juga menyediakan dokumentasi interaktif dari aplikasi yang sedang berjalan. Setelah dependency terpasang dan server Laravel aktif, buka:

- `http://127.0.0.1:8000/docs/api`
- `http://127.0.0.1:8000/docs/api.json`

## 6. Pengujian otomatis

Jalankan seluruh test dengan PHP lokal:

~~~bash
php artisan test
~~~

Jika Laravel dijalankan melalui Docker:

~~~powershell
docker exec -it praktikum-interoperabilitas-laravel php artisan test
~~~

Test mencakup registrasi, validasi login, mode abilities, profile, logout, BREAD, kepemilikan blog, dan penolakan akses ketika token tidak memiliki ability yang diperlukan.
