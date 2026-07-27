USE coffee_shop_db;

CREATE TABLE IF NOT EXISTS add_ons (
    addon_id    INT           AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0,
    category    VARCHAR(50)   DEFAULT NULL,
    status      ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_addons (
    order_addon_id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id  INT           NOT NULL,
    addon_id       INT           DEFAULT NULL,
    addon_name     VARCHAR(100)  NOT NULL,
    price          DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id) ON DELETE CASCADE,
    FOREIGN KEY (addon_id)      REFERENCES add_ons(addon_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE order_items
    ADD COLUMN size         VARCHAR(20)  DEFAULT NULL AFTER price,
    ADD COLUMN temperature  VARCHAR(20)  DEFAULT NULL AFTER size,
    ADD COLUMN sugar_level  VARCHAR(10)  DEFAULT NULL AFTER temperature,
    ADD COLUMN ice_level    VARCHAR(20)  DEFAULT NULL AFTER sugar_level,
    ADD COLUMN instructions TEXT         DEFAULT NULL AFTER ice_level;

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id      INT           AUTO_INCREMENT PRIMARY KEY,
    user_id     INT           DEFAULT NULL,
    action      VARCHAR(100)  NOT NULL,
    description TEXT          DEFAULT NULL,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS held_orders (
    hold_id      INT           AUTO_INCREMENT PRIMARY KEY,
    order_data   LONGTEXT      NOT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    held_by      INT           DEFAULT NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (held_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE transactions ADD COLUMN reference_number VARCHAR(50) DEFAULT NULL AFTER payment_method;
ALTER TABLE transactions ADD COLUMN cash_received DECIMAL(10,2) DEFAULT NULL AFTER reference_number;
ALTER TABLE transactions ADD COLUMN change_due DECIMAL(10,2) DEFAULT NULL AFTER cash_received;
ALTER TABLE transactions ADD COLUMN discount_type VARCHAR(50) DEFAULT NULL AFTER change_due;
ALTER TABLE transactions ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER discount_type;
ALTER TABLE transactions ADD COLUMN receipt_number VARCHAR(50) DEFAULT NULL AFTER discount_amount;

INSERT INTO add_ons (name, price, category) VALUES
('Extra Espresso Shot', 20.00, 'coffee'),
('Oat Milk', 25.00, 'milk'),
('Almond Milk', 25.00, 'milk'),
('Soy Milk', 25.00, 'milk'),
('Vanilla Syrup', 15.00, 'syrup'),
('Caramel Syrup', 15.00, 'syrup'),
('Hazelnut Syrup', 15.00, 'syrup'),
('Whipped Cream', 10.00, 'topping'),
('Chocolate Drizzle', 15.00, 'topping');
