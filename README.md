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

- PHP 8.3 atau lebih baru dan Composer 2 untuk menjalankan Laravel secara langsung; atau Docker Desktop untuk menjalankan Laravel tanpa memasang PHP di Windows.
- Database SQLite (sudah disiapkan pada metode Docker) atau MySQL/MariaDB untuk konfigurasi lokal.
- Node.js 24 atau lebih baru untuk Express dan Prism.
- Docker Engine/Docker Desktop untuk metode Laravel berbasis container dan opsi container Express.

Laravel Herd dan ServBay bukan persyaratan wajib. Jika PHP lokal tidak tersedia atau diblokir kebijakan Windows, gunakan metode Docker di bawah.

## Menjalankan Laravel API

Pilih salah satu metode berikut. Herd dan ServBay tidak wajib digunakan.

### Opsi A — Docker Desktop (Windows, tanpa PHP lokal)

Metode ini disediakan untuk komputer yang belum memiliki PHP/Composer atau yang memblokir executable PHP lokal.

1. Pasang dan buka Docker Desktop. Tunggu sampai Docker Engine berstatus **Running**.
2. Buka PowerShell di folder utama repository.
3. Jalankan script:

~~~powershell
powershell -ExecutionPolicy Bypass -File .\scripts\start-laravel.ps1
~~~

Pada penggunaan pertama, script akan membangun image PHP 8.4, membuat file .env jika belum ada, menyiapkan SQLite, memasang dependency Composer, membuat application key jika belum tersedia, menjalankan migration, lalu menyalakan server Laravel.

Base URL API: http://127.0.0.1:8000/api.

Untuk melihat log server:

~~~powershell
docker logs -f praktikum-interoperabilitas-laravel
~~~

Untuk menjalankan pengujian Laravel di dalam container:

~~~powershell
docker exec -it praktikum-interoperabilitas-laravel php artisan test
~~~

Untuk menghentikan dan menghapus container (file project, .env, dan database lokal tetap berada di folder repository):

~~~powershell
powershell -ExecutionPolicy Bypass -File .\scripts\stop-laravel.ps1
~~~

Script menggunakan docker run langsung, sehingga Docker Compose tidak diperlukan.

### Opsi B — PHP dan Composer lokal

Jika PHP 8.3+ dan Composer 2 sudah dapat dijalankan di Windows:

1. Instal dependency:

~~~bash
composer install
~~~

2. Salin file environment jika belum tersedia:

~~~powershell
Copy-Item .env.example .env
~~~

3. Buat application key:

~~~bash
php artisan key:generate
~~~

4. Atur koneksi database pada .env. Untuk SQLite, gunakan DB_CONNECTION=sqlite dan buat file database/database.sqlite jika belum tersedia. Untuk MySQL/MariaDB, isi host, port, nama database, username, dan password sesuai konfigurasi lokal.

5. Jalankan migration dan seeder:

~~~bash
php artisan migrate --seed
~~~

6. Jalankan server:

~~~bash
php artisan serve
~~~

Base URL API: http://127.0.0.1:8000/api.
## Dokumentasi dan pengujian Laravel

- OpenAPI: lihat file [OPENAPI.md](OPENAPI.md) dan [openapi.json](openapi.json).
- Dokumentasi interaktif Scramble: `http://127.0.0.1:8000/docs/api`.
- Daftar route: php artisan route:list --path=api; jika menggunakan Docker, jalankan docker exec -it praktikum-interoperabilitas-laravel php artisan route:list --path=api.
- Jalankan test Laravel: php artisan test; jika menggunakan Docker, jalankan docker exec -it praktikum-interoperabilitas-laravel php artisan test.

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
