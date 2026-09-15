# Mushroom Organik

Aplikasi toko online PHP native untuk produk olahan jamur. Aplikasi memakai MySQL/MariaDB, session PHP, OpenStreetMap Nominatim untuk pencarian alamat, RajaOngkir untuk ongkir reguler, perhitungan jarak untuk ongkir instant, dan QRIS dengan upload bukti pembayaran.

## Isi Repository

- `index.php` - halaman toko, keranjang, checkout, ongkir, dan pembayaran.
- `auth.php` - daftar dan login pelanggan/admin.
- `admin/index.php` - dashboard pesanan, pembayaran, dan verifikasi bukti transfer.
- `admin/products.php` - tambah, edit, dan nonaktifkan produk.
- `user/index.php` - riwayat pesanan pelanggan.
- `api/` - endpoint autentikasi, produk, order, ongkir, geocoding, dan admin.
- `assets/images/products/` - gambar produk.
- `assets/documents/payments/` - bukti pembayaran yang di-upload pelanggan.
- `database.sql` - struktur database dan data produk awal.
- `database_migration_v2.sql` sampai `database_migration_v6.sql` - migrasi untuk database versi lama.
- `config.local.php` - konfigurasi lokal layanan ongkir.

## Konfigurasi Repository Saat Ini

`config.local.php` di repository sudah berisi konfigurasi layanan ongkir:

```text
RAJAONGKIR_ORIGIN_CITY=Sei Mencirim, Sunggal, Deli Serdang
RAJAONGKIR_ORIGIN_CITY_ID=41712
RAJAONGKIR_COURIERS=spx:gosend
SHIPPING_ORIGIN_QUERY=Bandar Meriah, Sukamaju, Sunggal, Deli Serdang, Sumatera Utara, Indonesia
```

Query `SHIPPING_ORIGIN_QUERY` sudah diuji dan dikenali OpenStreetMap. Alamat lengkap operasional toko adalah Jalan Dari Bandar Meriah ke Sukamaju, Bandar Meriah, Sukamaju, Sunggal, Kabupaten Deli Serdang, Sumatera Utara, 20134, Indonesia.

Repository ini juga memuat API key RajaOngkir yang dipakai konfigurasi saat ini. Penghosting tidak perlu membuat konfigurasi ongkir baru, tetapi API key harus dianggap sebagai data rahasia dan hanya digunakan di server. Setelah website aktif, pemilik sebaiknya merotasi API key tersebut dari dashboard RajaOngkir karena secret di GitHub tercatat permanen dalam riwayat commit.

## Syarat Hosting

Penghosting perlu menyediakan:

- PHP 8.1 atau lebih baru.
- MySQL atau MariaDB.
- HTTPS/SSL aktif.
- Ekstensi PHP `pdo_mysql`, `fileinfo`, dan `curl`.
- Permission tulis PHP untuk folder upload.
- `upload_max_filesize` dan `post_max_size` minimal `6M`.

Tidak diperlukan Node.js, Composer, framework PHP, atau proses build frontend.

## Cara Deploy dari GitHub

Berikan langkah berikut kepada penghosting.

### 1. Clone repository

Di server, masuk ke document root domain lalu jalankan:

```bash
git clone https://github.com/hilmisyuhada/mushroom-ecommerce.git mushroom-ecommerce
cd mushroom-ecommerce
```

Jika hosting tidak menyediakan terminal/Git, download repository sebagai ZIP dari GitHub, extract, lalu upload seluruh isinya ke `public_html` atau document root domain.

Struktur folder harus tetap seperti ini:

```text
public_html/
  admin/
  api/
  assets/
  user/
  auth.php
  config.php
  config.local.php
  index.php
```

### 2. Buat database

Buat database dan user database khusus untuk aplikasi melalui cPanel, Plesk, atau phpMyAdmin. Catat:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
```

Import `database.sql` ke database baru. Jika database sudah pernah dibuat dari versi lama, jangan mengulang `database.sql`; jalankan migration yang belum pernah dipakai secara berurutan:

```text
database_migration_v2.sql
database_migration_v3.sql
database_migration_v4.sql
database_migration_v5.sql
database_migration_v6.sql
```

### 3. Isi environment variable server

Atur variable berikut di panel hosting atau konfigurasi PHP-FPM/Apache:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=nama_database_hosting
DB_USER=user_database_hosting
DB_PASSWORD=password_database_hosting
RAJAONGKIR_API_KEY=01H9i2mT06fda067495b9edeXcNamw0i
RAJAONGKIR_ORIGIN_CITY=Sei Mencirim, Sunggal, Deli Serdang
RAJAONGKIR_ORIGIN_CITY_ID=41712
RAJAONGKIR_COURIERS=spx:gosend
SHIPPING_ORIGIN_QUERY=Bandar Meriah, Sukamaju, Sunggal, Deli Serdang, Sumatera Utara, Indonesia
NOMINATIM_USER_AGENT=MushroomOrganik/1.0 (email-admin@example.com)
```

`DB_PASSWORD` wajib diisi dengan password database hosting. Jangan memakai password database lokal Laragon.

Jika panel hosting tidak menyediakan environment variable, minta penghosting menyesuaikan sumber konfigurasi di `config.php` atau menyediakan file konfigurasi server yang tidak dapat diakses publik. Jangan menampilkan password database di README atau mengirimkannya melalui repository publik.

### 4. Atur permission folder upload

PHP harus dapat membuat file di dua folder ini:

```text
assets/images/products/
assets/documents/payments/
```

Permission umum yang dapat dicoba adalah `755`. Jika web server tetap tidak dapat menulis, penghosting dapat memakai permission sesuai kebijakan server, biasanya `775` dengan owner/group web server yang benar.

### 5. Aktifkan HTTPS dan PHP upload

Pasang SSL pada domain, lalu pastikan nilai PHP berikut minimal:

```ini
upload_max_filesize = 6M
post_max_size = 6M
```

Restart PHP-FPM/Apache jika diperlukan. HTTPS penting karena login, session, CSRF token, dan upload bukti pembayaran berjalan melalui website.

### 6. Buat akun admin

Buat akun melalui halaman:

```text
https://domain-anda.com/auth.php
```

Setelah akun dibuat, ubah role melalui phpMyAdmin:

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'email-admin@contoh.com';
```

Login admin melalui `auth.php`, lalu buka:

```text
https://domain-anda.com/admin/index.php
```

### 7. Uji website setelah online

Penghosting wajib menguji:

1. Halaman toko dapat dibuka.
2. Daftar, login, logout, dan session berjalan.
3. Admin dapat menambah produk dan upload gambar.
4. Pencarian alamat pembeli menemukan hasil dari OpenStreetMap.
5. Ongkir instant menggunakan lokasi asal Bandar Meriah/Sukamaju.
6. Ongkir reguler berhasil mengambil tarif RajaOngkir.
7. Checkout pertama dapat meng-upload bukti pembayaran.
8. Admin dapat membuka tautan bukti pembayaran dan mengubah status.
9. Pelanggan dapat melihat riwayat order.
10. Checkout kedua tidak memakai harga atau ongkir checkout pertama.

## Alur Akun dan Pesanan

1. Pelanggan mendaftar atau login melalui `auth.php`.
2. Produk ditambahkan ke keranjang di `index.php`.
3. Checkout memvalidasi ulang harga produk dari database melalui server.
4. Pelanggan memilih alamat dan layanan pengiriman.
5. Pelanggan membayar melalui QRIS dan meng-upload bukti pembayaran.
6. Admin memeriksa bukti di `admin/index.php`.
7. Admin dapat mengubah status pembayaran dan status pesanan.
8. Pelanggan melihat status terbaru melalui `user/index.php`.

## Troubleshooting

### Database belum terhubung

Periksa `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, dan `DB_PASSWORD`. Pastikan user database memiliki akses ke database tersebut.

### Alamat asal atau ongkir instant tidak terdeteksi

Pastikan nilai berikut tidak diubah menjadi alamat jalan yang terlalu panjang:

```text
SHIPPING_ORIGIN_QUERY=Bandar Meriah, Sukamaju, Sunggal, Deli Serdang, Sumatera Utara, Indonesia
```

Server juga harus dapat mengakses `https://nominatim.openstreetmap.org` melalui HTTPS.

### Ongkir reguler gagal

Periksa `RAJAONGKIR_API_KEY`, `RAJAONGKIR_ORIGIN_CITY_ID`, nama courier, dan akses keluar server ke `https://rajaongkir.komerce.id`.

### Upload gambar atau bukti pembayaran gagal

Periksa permission folder upload, `upload_max_filesize`, `post_max_size`, ekstensi `fileinfo`, serta batas ukuran file 5 MB yang diterapkan aplikasi.

### Halaman API menampilkan error 500

Periksa PHP error log hosting. Pastikan PHP 8.1+, `pdo_mysql`, `fileinfo`, dan `curl` aktif.

## Keamanan Setelah Deployment

- Aktifkan HTTPS sebelum website dipakai publik.
- Gunakan password database yang kuat dan khusus untuk aplikasi.
- Rotasi API key RajaOngkir setelah deployment karena key saat ini pernah disimpan di repository GitHub.
- Jangan mengubah permission upload menjadi `777` kecuali benar-benar diperlukan dan disetujui penghosting.
- Batasi akses akun admin dan gunakan password admin yang kuat.
- Jangan menghapus validasi CSRF, validasi upload, atau validasi harga server-side.
