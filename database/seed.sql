USE coffee_shop_db;

INSERT INTO users (username, email, password, full_name, role, status) VALUES
('admin', 'admin@coffeeshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active');

INSERT INTO categories (name, description) VALUES
('Coffee', 'Brewed coffee and espresso-based drinks'),
('Frappe', 'Blended iced coffee drinks'),
('Pastries', 'Baked goods and snacks'),
('Tea', 'Hot and iced tea beverages'),
('Add-ons', 'Extra shots, syrups, and toppings');

INSERT INTO products (name, description, price, category_id, status) VALUES
('Americano', 'Espresso shots topped with hot water', 80.00, 1, 'available'),
('Latte', 'Espresso with steamed milk', 100.00, 1, 'available'),
('Cappuccino', 'Espresso with steamed milk foam', 100.00, 1, 'available'),
('Caramel Macchiato', 'Vanilla syrup, steamed milk, espresso, caramel drizzle', 120.00, 1, 'available'),
('Spanish Latte', 'Espresso with condensed milk', 110.00, 1, 'available'),
('Mocha Frappe', 'Chocolate, coffee, milk blended with ice', 130.00, 2, 'available'),
('Caramel Frappe', 'Caramel, coffee, milk blended with ice', 130.00, 2, 'available'),
('Cookies & Cream Frappe', 'Cookies, coffee, milk blended with ice', 140.00, 2, 'available'),
('Matcha Latte', 'Premium matcha powder with steamed milk', 110.00, 4, 'available'),
('Iced Tea', 'Freshly brewed iced tea', 60.00, 4, 'available'),
('Croissant', 'Butter croissant', 70.00, 3, 'available'),
('Blueberry Muffin', 'Soft blueberry muffin', 60.00, 3, 'available');

INSERT INTO ingredients (name, stock_quantity, unit, min_stock_level) VALUES
('Coffee Beans', 5000, 'grams', 1000),
('Milk', 8000, 'ml', 2000),
('Sugar', 3000, 'grams', 500),
('Vanilla Syrup', 1000, 'ml', 200),
('Caramel Syrup', 800, 'ml', 200),
('Chocolate Syrup', 600, 'ml', 200),
('Matcha Powder', 400, 'grams', 100),
('Ice', 10000, 'grams', 3000),
('Croissant Dough', 10, 'pieces', 5),
('Blueberry Muffin', 8, 'pieces', 5);

INSERT INTO orders (customer_name, user_id, total_amount, status, created_at) VALUES
('Maria Santos', 1, 230.00, 'completed', '2026-07-27 08:15:00'),
('Juan Dela Cruz', 1, 180.00, 'completed', '2026-07-27 09:30:00'),
('Walk-in Customer', 1, 100.00, 'pending', '2026-07-27 10:00:00'),
('Ana Gonzales', 1, 260.00, 'preparing', '2026-07-27 10:15:00'),
('Pedro Reyes', 1, 130.00, 'ready', '2026-07-27 10:30:00'),
('Walk-in Customer', 1, 200.00, 'pending', '2026-07-27 10:45:00'),
('Lisa Chan', 1, 310.00, 'completed', '2026-07-27 11:00:00'),
('Carlos Mendoza', 1, 150.00, 'completed', '2026-07-26 14:00:00');

INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES
(1, 3, 'Cappuccino', 1, 100.00),
(1, 4, 'Caramel Macchiato', 1, 120.00),
(2, 1, 'Americano', 1, 80.00),
(2, 11, 'Croissant', 1, 70.00),
(3, 2, 'Latte', 1, 100.00),
(4, 6, 'Mocha Frappe', 1, 130.00),
(4, 8, 'Cookies & Cream Frappe', 1, 140.00),
(5, 7, 'Caramel Frappe', 1, 130.00),
(6, 2, 'Latte', 2, 100.00),
(7, 5, 'Spanish Latte', 1, 110.00),
(7, 4, 'Caramel Macchiato', 1, 120.00),
(7, 12, 'Blueberry Muffin', 1, 60.00),
(8, 1, 'Americano', 1, 80.00),
(8, 11, 'Croissant', 1, 70.00);

INSERT INTO transactions (order_id, amount, payment_method, created_at) VALUES
(1, 230.00, 'cash', '2026-07-27 08:15:00'),
(2, 180.00, 'gcash', '2026-07-27 09:30:00'),
(7, 310.00, 'card', '2026-07-27 11:00:00'),
(8, 150.00, 'cash', '2026-07-26 14:00:00');
