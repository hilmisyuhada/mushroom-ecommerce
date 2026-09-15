USE mushroom_organik;

-- Akun admin awal untuk deployment. Ganti password setelah login pertama.
INSERT INTO users (name, email, phone, password_hash, role)
VALUES (
    'Admin Mushroom Organik',
    'admin@mushroomorganik.com',
    '6282168576896',
    '$2y$10$T4o1GMSQTvqaBC/dnvJwyOEgE2gcT87D.puWR2tdmmrxDElBEIvbm',
    'admin'
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    phone = VALUES(phone),
    password_hash = VALUES(password_hash),
    role = 'admin';

INSERT INTO store_settings (setting_key, setting_value)
VALUES ('contact_whatsapp', '6282168576896')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);