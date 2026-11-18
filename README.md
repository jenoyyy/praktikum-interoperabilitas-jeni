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
- Jalankan test Laravel: `php artisan test`.

## Pengujian otomatis Node.js dan OpenAPI

Instal dependency Node.js jika belum dilakukan:

~~~bash
npm ci
~~~

Jalankan pengujian integrasi Express dan pemeriksaan kontrak OpenAPI:

~~~bash
npm test
~~~

Pengujian ini memeriksa endpoint produk Express (GET, POST, PATCH, DELETE), validasi, respons 404/409/422, serta struktur endpoint dan token abilities pada `openapi.json`.

## Pengujian manual dengan Insomnia

1. Pastikan Laravel berjalan pada `http://127.0.0.1:8000`, database sudah dikonfigurasi, dan migration sudah dijalankan.
2. Impor file `insomnia/Praktikum-Interoperabilitas-Blog-API.insomnia.json` ke Insomnia melalui **Import**.
3. Pilih environment **Local Laravel API**. Base URL sudah diatur ke `http://127.0.0.1:8000/api`.
4. Sebelum menjalankan request, ubah `owner_email` dan `other_email` menjadi alamat email uji yang belum terdaftar. Gunakan `test_password` yang sama untuk kedua akun.
5. Jalankan request secara berurutan sesuai grup: **01 Authentication**, **02 Blog BREAD**, **03 Owner Access Control**, lalu **04 Finish**.
6. Setelah request login pemilik berhasil, salin nilai `token` dari respons ke environment variable `owner_token`. Setelah login pengguna kedua, salin tokennya ke `other_token`. Setelah Add Blog berhasil, salin `data.id` ke `blog_id`.
7. Pastikan request pengguna kedua untuk membaca, mengubah, dan menghapus blog pemilik menghasilkan `403 Forbidden`.
8. Pengujian membuat dua akun dan satu blog pada database lokal. Request terakhir menghapus blog uji dan melakukan logout akun pemilik; akun uji tetap tersimpan.

File koleksi Postman lama tetap tersedia di folder `postman/` sebagai alternatif, tetapi panduan pengujian utama project ini menggunakan Insomnia.

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
