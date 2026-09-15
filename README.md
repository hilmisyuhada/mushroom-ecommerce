# Mushroom Organik

## Menjalankan dengan MySQL

1. Pastikan PHP memiliki ekstensi `pdo_mysql` dan MySQL/MariaDB sedang berjalan.
2. Import `database.sql` melalui phpMyAdmin atau terminal MySQL.
	Jika database lama sudah pernah dibuat dari versi sebelumnya, import `database_migration_v2.sql`, `database_migration_v3.sql`, `database_migration_v4.sql`, lalu `database_migration_v5.sql` terlebih dahulu.
		Untuk fitur pengiriman baru, alamat dipilih melalui OpenStreetMap. Tidak perlu import tabel wilayah; sistem mengambil destination RajaOngkir berdasarkan alamat hasil reverse geocoding dan menyimpan hasilnya di cache session.

kalau belum, ibu bisa impor dulu database luaran yg saya kasih

3. Jika konfigurasi lokal bukan default, set environment variable berikut:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=mushroom_organik
DB_USER=root
DB_PASSWORD=
RAJAONGKIR_API_KEY=isi_api_key_rajaongkir_di_server
RAJAONGKIR_ORIGIN_CITY=Sei Mencirim, Sunggal, Deli Serdang
RAJAONGKIR_ORIGIN_CITY_ID=41712
RAJAONGKIR_COURIERS=jne:pos:tiki
SHIPPING_ORIGIN_QUERY=Sei Mencirim, Sunggal, Deli Serdang, Sumatera Utara, Indonesia
NOMINATIM_USER_AGENT=MushroomOrganik/1.0 (email-admin@example.com)
```

4. Jalankan dari folder proyek:

```text
php -S localhost:8000
```

5. Buka `http://localhost:8000/index.php`.

## Hosting publik

Berikan langkah berikut kepada pihak hosting atau developer yang melakukan deployment:

1. Pastikan hosting menyediakan PHP 8.1 atau lebih baru, MySQL/MariaDB, SSL/HTTPS, serta ekstensi `pdo_mysql`, `fileinfo`, dan `curl`.
2. Buat satu database dan satu user database khusus untuk aplikasi. Catat host, port, nama database, username, dan passwordnya.
3. Upload seluruh isi folder proyek ke document root domain, misalnya `public_html`. Jangan mengubah struktur folder seperti `api/`, `admin/`, `user/`, dan `assets/`.
4. Import `database.sql` ke database baru. Untuk database lama, jalankan migration secara berurutan sesuai versi yang belum pernah dijalankan.
5. Atur environment variable server berikut. Nilai `DB_PASSWORD` dan `RAJAONGKIR_API_KEY` tidak boleh dikosongkan di hosting publik:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=mushroom_organik
DB_USER=user_database
DB_PASSWORD=password_database
RAJAONGKIR_API_KEY=api_key_rajaongkir
RAJAONGKIR_ORIGIN_CITY=Sei Mencirim, Sunggal, Deli Serdang
RAJAONGKIR_ORIGIN_CITY_ID=41712
RAJAONGKIR_COURIERS=spx:gosend
SHIPPING_ORIGIN_QUERY=Bandar Meriah, Sukamaju, Sunggal, Deli Serdang, Sumatera Utara, Indonesia
NOMINATIM_USER_AGENT=MushroomOrganik/1.0 (email-admin@example.com)
```

6. Jika hosting tidak menyediakan environment variable, salin `config.local.php` menjadi konfigurasi lokal server dan isi kredensial database di sana. Jangan mengunggah API key asli ke repository publik; API key yang pernah tersimpan di file lokal sebaiknya dibuat ulang atau dirotasi.
7. Pastikan folder berikut dapat ditulis oleh PHP/web server karena dipakai untuk upload gambar produk dan bukti pembayaran:

```text
assets/images/products/
assets/documents/payments/
```

8. Atur `upload_max_filesize` dan `post_max_size` minimal 6M, lalu restart PHP-FPM/Apache bila pengaturan PHP diubah.
9. Aktifkan HTTPS dan pastikan domain membuka `index.php`. Tidak diperlukan framework atau proses build Node.js.
10. Buat akun melalui `auth.php`, lalu jadikan admin sekali melalui phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'email-admin@contoh.com';
```

11. Uji setelah online: pendaftaran/login, tambah produk dari admin, pencarian alamat toko dan alamat pembeli di peta, hitung ongkir instant/reguler, upload bukti pembayaran, buka bukti dari `admin/index.php`, serta checkout kedua dengan keranjang baru.

Jika muncul pesan `Database belum terhubung`, periksa environment variable database. Jika alamat atau ongkir gagal, periksa `SHIPPING_ORIGIN_QUERY`, API key RajaOngkir, akses keluar HTTPS dari server, dan `NOMINATIM_USER_AGENT`.

## Alur akun dan pesanan

1. Pelanggan membuat akun atau login melalui `auth.php`.
2. Checkout hanya dapat dilakukan setelah login. Server mengambil ulang harga dan produk dari database, lalu menyimpan order milik akun tersebut.
3. Pembayaran dilakukan melalui QRIS. Pelanggan mengunggah bukti pembayaran, lalu admin memverifikasi statusnya dari dashboard.
4. Buat akun admin melalui halaman daftar biasa, kemudian ubah role akun tersebut sekali melalui phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'email-admin@contoh.com';
```

5. Login dengan akun admin dan buka `admin/index.php` untuk melihat pembayaran serta mengubah status order menjadi `Sedang dikemas`, `Telah dikirim`, atau `Sudah sampai`.
6. Dashboard user tersedia di `user/index.php` untuk melihat riwayat pesanan dan status pembayaran.
7. Kelola produk admin tersedia di `admin/products.php` untuk menambah, mengedit, atau menonaktifkan produk.

Harga produk selalu diambil ulang dari tabel `products` di server. Password disimpan menggunakan `password_hash`, endpoint sensitif menggunakan session dan CSRF token, serta file bukti diberi nama acak. Pencarian alamat dan reverse geocoding memakai OpenStreetMap Nominatim dan disimpan di session agar tidak diulang. Ongkir instant dihitung dari jarak Haversine; ongkir reguler menggunakan alamat OSM untuk mencari destination dan melakukan satu hit tarif RajaOngkir gabungan, lalu hasilnya di-cache.


API key jangan ditulis di source code. Atur `RAJAONGKIR_API_KEY` sebagai environment variable server. ID kota asal dicari otomatis berdasarkan `RAJAONGKIR_ORIGIN_CITY` (default `Medan`), jadi `RAJAONGKIR_ORIGIN_CITY_ID` tidak perlu diisi. Untuk deployment publik, aktifkan HTTPS dan gunakan password database non-kosong.


