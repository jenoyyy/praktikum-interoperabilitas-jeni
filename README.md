# Praktikum Interoperabilitas — Blog API

Project praktikum REST API menggunakan Laravel, Sanctum, OpenAPI, dan Express.js.

## Fitur Laravel API

- Register, login, profile, dan logout menggunakan Laravel Sanctum.
- BREAD Blog: Browse, Read, Edit, Add, Delete.
- Relasi one-to-many: satu User dapat memiliki banyak Blog.
- Setiap blog menyimpan `user_id` pemilik.
- Token abilities: `blogs:read`, `blogs:update`, `blogs:create`, dan `blogs:delete`.
- Pemeriksaan kepemilikan untuk detail, edit, dan hapus blog.
- Dokumentasi kontrak OpenAPI 3.0.3 pada `openapi.json`.
- Dokumentasi API interaktif melalui Laravel Scramble.

## Persyaratan

- PHP 8.3 atau lebih baru.
- Composer 2.
- Database SQLite atau MySQL/MariaDB.
- Node.js 24 atau lebih baru untuk Express dan Prism.
- Docker (opsional, untuk menjalankan Express API dalam container).

## Menjalankan Laravel API

1. Clone repository (jika belum ada di komputer):

~~~bash
git clone https://github.com/jenoyyy/praktikum-interoperabilitas-jeni.git
cd praktikum-interoperabilitas-jeni
~~~

Jika repository sudah pernah di-clone, pastikan menggunakan branch utama dan ambil perubahan terbaru:

~~~bash
git switch main
git pull origin main
~~~

2. Instal dependency:

~~~bash
composer install
~~~

3. Salin file environment. Pada Windows PowerShell:

~~~powershell
Copy-Item .env.example .env
~~~

4. Buat application key:

~~~bash
php artisan key:generate
~~~

5. Atur koneksi database di `.env`.

Untuk SQLite, gunakan `DB_CONNECTION=sqlite`. Jika file database belum ada, buat `database/database.sqlite` terlebih dahulu. Untuk MySQL/MariaDB, isi host, port, nama database, username, dan password sesuai konfigurasi lokal.

6. Jalankan migration dan seeder:

~~~bash
php artisan migrate --seed
~~~

7. Jalankan server:

~~~bash
php artisan serve
~~~

Base URL API: `http://127.0.0.1:8000/api`.

## Dokumentasi dan pengujian Laravel

- OpenAPI: lihat file [OPENAPI.md](OPENAPI.md) dan [openapi.json](openapi.json).
- Dokumentasi interaktif Scramble: `http://127.0.0.1:8000/docs/api`.
- Daftar route: `php artisan route:list --path=api`.
- Jalankan test: `php artisan test`.

## Menjalankan Express HTTP API

Express API merupakan praktikum terpisah dari Laravel dan berjalan pada port 3000.

~~~bash
npm ci
npm run start:api
~~~

Buka `http://localhost:3000`. Endpoint produk tersedia pada:

- `GET /produk`
- `GET /produk/:id`
- `POST /produk`
- `PATCH /produk/:id`
- `DELETE /produk/:id`

Data produk Express disimpan sementara di memori proses. Data akan kembali ke data awal ketika server dihentikan atau dimulai ulang.

## Menjalankan Express dengan Docker

~~~bash
docker build -t praktikum-http-api .
docker run --rm -p 3000:3000 praktikum-http-api
~~~

## Catatan keamanan

Mode login `read`, `edit`, `delete`, dan `manage` dibuat untuk memperagakan token abilities sesuai alur praktikum. Untuk aplikasi produksi, pemberian abilities harus ditentukan oleh izin server-side dan kebijakan role yang tepercaya.
