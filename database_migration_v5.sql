USE mushroom_organik;

CREATE TABLE IF NOT EXISTS store_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO store_settings (setting_key, setting_value) VALUES
('about_title', 'Dari Budidaya Jamur hingga Produk Bernilai'),
('about_description', 'Mushroom Organik hadir dengan semangat mengembangkan jamur tiram menjadi produk pangan yang berkualitas, inovatif, dan memiliki nilai tambah.'),
('about_badge_title', 'Integrated Healthy Food Ecosystem'),
('about_badge_text', 'Menghubungkan hulu ke hilir dalam pangan sehat, plant-based, natural, dan shelf stable.'),
('contact_whatsapp', '6282168576896'),
('contact_email', 'bestonesolution.global@gmail.com'),
('contact_instagram', 'bosglobal.id')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
