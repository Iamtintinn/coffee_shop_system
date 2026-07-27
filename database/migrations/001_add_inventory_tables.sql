USE coffee_shop_db;

ALTER TABLE ingredients
    ADD COLUMN expiration_date DATE DEFAULT NULL AFTER min_stock_level,
    ADD COLUMN last_restocked_at TIMESTAMP NULL DEFAULT NULL AFTER expiration_date;

CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id   INT          AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100) DEFAULT NULL,
    phone         VARCHAR(20)  DEFAULT NULL,
    email         VARCHAR(100) DEFAULT NULL,
    address       TEXT         DEFAULT NULL,
    status        ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
    movement_id    INT           AUTO_INCREMENT PRIMARY KEY,
    ingredient_id  INT           NOT NULL,
    type           ENUM('in', 'out') NOT NULL,
    quantity       DECIMAL(10,2) NOT NULL,
    reference      VARCHAR(100)  DEFAULT NULL,
    notes          TEXT          DEFAULT NULL,
    performed_by   INT           DEFAULT NULL,
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(ingredient_id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by)   REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
