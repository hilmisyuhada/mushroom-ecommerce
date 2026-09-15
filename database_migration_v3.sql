USE mushroom_organik;

ALTER TABLE products
    ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT 'SNACK JAMUR ORGANIK' AFTER weight_gram,
    ADD COLUMN badge VARCHAR(80) NULL AFTER category,
    ADD COLUMN claim VARCHAR(80) NULL AFTER badge,
    ADD COLUMN description TEXT NULL AFTER claim,
    ADD COLUMN composition VARCHAR(500) NULL AFTER description,
    ADD COLUMN highlight_one_icon VARCHAR(60) NULL AFTER composition,
    ADD COLUMN highlight_one_title VARCHAR(120) NULL AFTER highlight_one_icon,
    ADD COLUMN highlight_one_text VARCHAR(180) NULL AFTER highlight_one_title,
    ADD COLUMN highlight_two_icon VARCHAR(60) NULL AFTER highlight_one_text,
    ADD COLUMN highlight_two_title VARCHAR(120) NULL AFTER highlight_two_icon,
    ADD COLUMN highlight_two_text VARCHAR(180) NULL AFTER highlight_two_title,
    ADD COLUMN rating DECIMAL(2,1) NOT NULL DEFAULT 0.0 AFTER highlight_two_text,
    ADD COLUMN sold_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER rating;