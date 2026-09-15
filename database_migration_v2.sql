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

ALTER TABLE orders
    ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN admin_note VARCHAR(500) NULL AFTER proof_file,
    ADD COLUMN payment_verified_at TIMESTAMP NULL AFTER admin_note,
    ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
