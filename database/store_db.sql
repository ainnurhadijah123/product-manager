-- =========================================================
-- Database: store_db
-- Mini Project: Product Manager (Pemrograman Web - Pertemuan 3)
-- =========================================================

CREATE DATABASE IF NOT EXISTS store_db;
USE store_db;

CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)   NOT NULL UNIQUE,
    category    VARCHAR(50)    NOT NULL DEFAULT 'Umum',
    price       DECIMAL(12,2)  NOT NULL,
    stock       INT            NOT NULL DEFAULT 0,
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
);

-- Contoh data uji (opsional, boleh dihapus)
INSERT INTO products (name, category, price, stock) VALUES
('Keyboard Mekanikal', 'Aksesoris', 450000, 15),
('Mouse Wireless', 'Aksesoris', 175000, 30),
('Monitor 24 Inch', 'Elektronik', 1850000, 8);
