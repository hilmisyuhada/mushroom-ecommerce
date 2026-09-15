USE mushroom_organik;

CREATE TABLE IF NOT EXISTS wilayah_rajaongkir (
    id_rajaongkir VARCHAR(30) PRIMARY KEY,
    nama_kecamatan VARCHAR(120) NOT NULL,
    nama_kota VARCHAR(120) NOT NULL,
    nama_provinsi VARCHAR(120) NOT NULL,
    KEY idx_wilayah_kecamatan (nama_kecamatan),
    KEY idx_wilayah_kota (nama_kota)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Import master kecamatan RajaOngkir ke tabel ini satu kali.
-- Contoh: INSERT INTO wilayah_rajaongkir (id_rajaongkir, nama_kecamatan, nama_kota, nama_provinsi) VALUES (...);