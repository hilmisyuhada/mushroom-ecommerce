USE mushroom_organik;

ALTER TABLE orders
    ADD COLUMN order_status VARCHAR(60) NOT NULL DEFAULT 'Menunggu pembayaran' AFTER payment_status;

UPDATE orders
SET payment_status = 'Menunggu pembayaran'
WHERE payment_status = 'Menunggu verifikasi pembayaran';

UPDATE orders
SET order_status = CASE payment_status
    WHEN 'Diproses' THEN 'Sedang dikemas'
    WHEN 'Dikirim' THEN 'Telah dikirim'
    WHEN 'Selesai' THEN 'Sudah sampai'
    ELSE payment_status
END
WHERE order_status = 'Menunggu pembayaran';