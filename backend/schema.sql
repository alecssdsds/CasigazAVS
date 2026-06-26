-- ====================================================
--  CASIGAZ - MySQL 8.0 schema
--  Importă în baza de date: 01144012_avs
-- ====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- CATEGORIES ----------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(150) NOT NULL,
    slug        VARCHAR(180) NOT NULL,
    description TEXT NULL,
    image_path  VARCHAR(255) NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    status      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- PRODUCTS ----------
CREATE TABLE IF NOT EXISTS products (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id       INT UNSIGNED NULL,
    name              VARCHAR(200) NOT NULL,
    slug              VARCHAR(220) NOT NULL,
    short_description VARCHAR(500) NULL,
    description       TEXT NULL,
    sku               VARCHAR(100) NULL,
    price             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    old_price         DECIMAL(10,2) NULL,
    price_on_request  TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = afișează "CERE OFERTĂ"
    stock             INT NOT NULL DEFAULT 0,
    in_stock          TINYINT(1) NOT NULL DEFAULT 1,  -- disponibilitate manuală
    featured          TINYINT(1) NOT NULL DEFAULT 0,
    status            TINYINT(1) NOT NULL DEFAULT 1,  -- 1 = vizibil
    seo_title         VARCHAR(255) NULL,
    seo_description   VARCHAR(500) NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category (category_id),
    KEY idx_products_status (status),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- PRODUCT IMAGES ----------
CREATE TABLE IF NOT EXISTS product_images (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id  INT UNSIGNED NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    is_main     TINYINT(1) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pi_product (product_id),
    CONSTRAINT fk_pi_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- CART ITEMS ----------
CREATE TABLE IF NOT EXISTS cart_items (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_token VARCHAR(64) NOT NULL,
    product_id    INT UNSIGNED NOT NULL,
    quantity      INT NOT NULL DEFAULT 1,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cart_token (session_token),
    UNIQUE KEY uq_cart_token_product (session_token, product_id),
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ORDERS ----------
CREATE TABLE IF NOT EXISTS orders (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(40) NOT NULL,
    first_name   VARCHAR(100) NOT NULL,
    last_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    phone        VARCHAR(50) NOT NULL,
    address      VARCHAR(255) NOT NULL,
    city         VARCHAR(120) NOT NULL,
    county       VARCHAR(120) NULL,
    postal_code  VARCHAR(20) NULL,
    country      VARCHAR(100) NULL DEFAULT 'România',
    company_name VARCHAR(150) NULL,
    cui          VARCHAR(50) NULL,
    notes        TEXT NULL,
    subtotal     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status       VARCHAR(20) NOT NULL DEFAULT 'noua',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_number (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ORDER ITEMS ----------
CREATE TABLE IF NOT EXISTS order_items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NULL,
    product_name VARCHAR(200) NOT NULL,
    price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity     INT NOT NULL DEFAULT 1,
    line_total   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_oi_order (order_id),
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id)
        REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- OFFERS (CERE OFERTĂ) ----------
CREATE TABLE IF NOT EXISTS offers (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id   INT UNSIGNED NULL,
    company_name VARCHAR(150) NULL,
    contact_name VARCHAR(150) NOT NULL,
    phone        VARCHAR(50) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    message      TEXT NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'noua',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_offers_product (product_id),
    CONSTRAINT fk_offers_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- SETTINGS ----------
CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key   VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------- DATE INIȚIALE ----------
INSERT INTO settings (setting_key, setting_value) VALUES
    ('site_name', 'Casigaz'),
    ('site_email', 'contact@casigaz-serv.ro'),
    ('site_phone', ''),
    ('site_address', ''),
    ('currency', 'RON'),
    ('shipping_cost', '0'),
    ('vat_rate', '19')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
