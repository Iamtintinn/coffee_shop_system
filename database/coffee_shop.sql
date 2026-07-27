CREATE DATABASE IF NOT EXISTS coffee_shop_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE coffee_shop_db;

DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_ingredients;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS ingredients;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id    INT             AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)     NOT NULL UNIQUE,
    email      VARCHAR(100)    NOT NULL UNIQUE,
    password   VARCHAR(255)    NOT NULL,
    full_name  VARCHAR(100)    DEFAULT NULL,
    role       ENUM('admin', 'manager', 'cashier') NOT NULL DEFAULT 'cashier',
    address    TEXT            DEFAULT NULL,
    phone      VARCHAR(20)     DEFAULT NULL,
    age        INT             DEFAULT NULL,
    id_upload  VARCHAR(255)    DEFAULT NULL,
    status     ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    category_id   INT          AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL UNIQUE,
    description   TEXT         DEFAULT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    product_id    INT           AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    description   TEXT          DEFAULT NULL,
    price         DECIMAL(10,2) NOT NULL,
    category_id   INT           DEFAULT NULL,
    image         VARCHAR(255)  DEFAULT NULL,
    status        ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ingredients (
    ingredient_id   INT           AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL UNIQUE,
    stock_quantity  DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit            VARCHAR(20)   NOT NULL DEFAULT 'pieces',
    min_stock_level   DECIMAL(10,2) NOT NULL DEFAULT 10,
    expiration_date   DATE          DEFAULT NULL,
    last_restocked_at TIMESTAMP     NULL DEFAULT NULL,
    created_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
    supplier_id    INT          AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100) DEFAULT NULL,
    phone          VARCHAR(20)  DEFAULT NULL,
    email          VARCHAR(100) DEFAULT NULL,
    address        TEXT         DEFAULT NULL,
    status         ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
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

CREATE TABLE product_ingredients (
    product_ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id            INT           NOT NULL,
    ingredient_id         INT           NOT NULL,
    quantity              DECIMAL(10,2) NOT NULL DEFAULT 1,
    FOREIGN KEY (product_id)    REFERENCES products(product_id)    ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(ingredient_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    order_id     INT           AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) DEFAULT 'Walk-in Customer',
    user_id      INT           DEFAULT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status       ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id      INT           NOT NULL,
    product_id    INT           DEFAULT NULL,
    product_name  VARCHAR(100)  NOT NULL,
    quantity      INT           NOT NULL DEFAULT 1,
    price         DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(order_id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
    transaction_id INT           AUTO_INCREMENT PRIMARY KEY,
    order_id       INT           NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'gcash', 'maya') NOT NULL DEFAULT 'cash',
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
