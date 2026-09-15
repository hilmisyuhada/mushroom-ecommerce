CREATE DATABASE IF NOT EXISTS mushroom_organik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mushroom_organik;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(80) PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    price INT UNSIGNED NOT NULL,
    image VARCHAR(255) NOT NULL,
    weight_gram SMALLINT UNSIGNED NOT NULL DEFAULT 80,
    category VARCHAR(120) NOT NULL DEFAULT 'SNACK JAMUR ORGANIK',
    badge VARCHAR(80) NULL,
    claim VARCHAR(80) NULL,
    description TEXT NULL,
    composition VARCHAR(500) NULL,
    highlight_one_icon VARCHAR(60) NULL,
    highlight_one_title VARCHAR(120) NULL,
    highlight_one_text VARCHAR(180) NULL,
    highlight_two_icon VARCHAR(60) NULL,
    highlight_two_title VARCHAR(120) NULL,
    highlight_two_text VARCHAR(180) NULL,
    rating DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    sold_count INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

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
('contact_whatsapp', '6282168576196'),
('contact_email', 'bestonesolution.global@gmail.com'),
('contact_instagram', 'bosglobal.id')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(32) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    province VARCHAR(80) NOT NULL,
    city VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    note VARCHAR(500) NULL,
    shipping_name VARCHAR(120) NOT NULL,
    shipping_cost INT UNSIGNED NOT NULL,
    subtotal INT UNSIGNED NOT NULL,
    total INT UNSIGNED NOT NULL,
    payment_status VARCHAR(60) NOT NULL DEFAULT 'Menunggu pembayaran',
    order_status VARCHAR(60) NOT NULL DEFAULT 'Menunggu pembayaran',
    proof_file VARCHAR(255) NULL,
    admin_note VARCHAR(500) NULL,
    payment_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ,CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(32) NOT NULL,
    product_id VARCHAR(80) NOT NULL,
    product_name VARCHAR(160) NOT NULL,
    unit_price INT UNSIGNED NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    line_total INT UNSIGNED NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

INSERT INTO products (id, name, price, image, weight_gram) VALUES
('jamur-krispi-original-natural', 'Jamur Krispi – Original Natural', 25000, 'assets/images/products/jamur-krispi-original-natural.png', 80),
('jamur-krispi-seaweed', 'Jamur Krispi – Seaweed', 25000, 'assets/images/products/jamur-krispi-seaweed.png', 80),
('jamur-krispi-cheese', 'Jamur Krispi – Cheese', 25000, 'assets/images/products/jamur-krispi-cheese.png', 80),
('jamur-krispi-balado', 'Jamur Krispi – Balado', 25000, 'assets/images/products/jamur-krispi-balado.png', 80),
('jamur-krispi-pizza', 'Jamur Krispi – Pizza', 25000, 'assets/images/products/jamur-krispi-pizza.png', 80),
('jamur-krispi-original', 'Jamur Krispi – Original', 25000, 'assets/images/products/jamur-krispi-original.png', 80),
('jamur-krispi-mie-goreng', 'Jamur Krispi – Mie Goreng', 25000, 'assets/images/products/jamur-krispi-mie-goreng.png', 80),
('jamur-krispi-seaweed-spicy', 'Jamur Krispi – Seaweed Spicy', 25000, 'assets/images/products/jamur-krispi-seaweed-spicy.png', 80)
ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price), image = VALUES(image), weight_gram = VALUES(weight_gram);
