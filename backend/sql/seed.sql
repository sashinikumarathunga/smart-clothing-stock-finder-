USE smart_stock_finder;

INSERT INTO branches (name, location, phone, loyalty_spend_per_point, loyalty_point_value, loyalty_min_redeem, return_period_days, low_stock_threshold) VALUES
('Colombo Main', 'No. 45, Galle Road, Colombo 03', '0112345678', 5000.00, 100.00, 5, 7, 5),
('Kandy City', 'No. 12, Peradeniya Road, Kandy', '0812345678', 5000.00, 100.00, 5, 7, 5);

INSERT INTO users (branch_id, role, full_name, username, password_hash, is_active) VALUES
(NULL, 'owner', 'System Owner', 'owner', '$2y$12$MShQ7FmBbJDXqzTKEc7sVOLxN6hOwdXUH163fybAMeMylmaD0VXQq', 1),
(1, 'branch_admin', 'Colombo Branch Admin', 'admin_colombo', '$2y$12$MShQ7FmBbJDXqzTKEc7sVOLxN6hOwdXUH163fybAMeMylmaD0VXQq', 1),
(1, 'storekeeper', 'Colombo Storekeeper', 'store_colombo', '$2y$12$MShQ7FmBbJDXqzTKEc7sVOLxN6hOwdXUH163fybAMeMylmaD0VXQq', 1),
(1, 'sales_assistant', 'Colombo Sales Assistant', 'sales_colombo', '$2y$12$MShQ7FmBbJDXqzTKEc7sVOLxN6hOwdXUH163fybAMeMylmaD0VXQq', 1),
(1, 'cashier', 'Colombo Cashier', 'cashier_colombo', '$2y$12$MShQ7FmBbJDXqzTKEc7sVOLxN6hOwdXUH163fybAMeMylmaD0VXQq', 1),
(2, 'branch_admin', 'Kandy Branch Admin', 'admin_kandy', '$2y$12$MShQ7FmBbJDXqzTKEc7sVOLxN6hOwdXUH163fybAMeMylmaD0VXQq', 1);

INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES
('Fashion Wholesale Lanka', 'Mr. Perera', '0771234567', 'orders@fwl.lk', 'Colombo 10'),
('Global Apparel Supply', 'Ms. Fernando', '0779876543', 'sales@globalapparel.lk', 'Negombo');

INSERT INTO products (branch_id, supplier_id, style_code, brand, name, category, size, color, price, quantity, location_in_store, barcode, low_stock_alert_enabled) VALUES
(1, 1, 'STY-1001', 'Levis', 'Classic Denim Shirt', 'Shirts', 'M', 'Blue', 4500.00, 12, 'Aisle A - Rack 1', 'BC-STY1001-M-BLU-CMB', 1),
(1, 1, 'STY-1001', 'Levis', 'Classic Denim Shirt', 'Shirts', 'L', 'Blue', 4500.00, 8, 'Aisle A - Rack 1', 'BC-STY1001-L-BLU-CMB', 1),
(1, 2, 'STY-1002', 'H&M', 'Cotton Polo Tee', 'T-Shirts', 'S', 'White', 3200.00, 15, 'Aisle B - Rack 3', 'BC-STY1002-S-WHT-CMB', 1),
(1, 2, 'STY-1003', 'Nike', 'Sport Joggers', 'Trousers', 'M', 'Black', 6800.00, 3, 'Aisle C - Rack 2', 'BC-STY1003-M-BLK-CMB', 1),
(2, 1, 'STY-1001', 'Levis', 'Classic Denim Shirt', 'Shirts', 'M', 'Blue', 4500.00, 5, 'Ground Floor - Zone 1', 'BC-STY1001-M-BLU-KDY', 1),
(2, 2, 'STY-1002', 'H&M', 'Cotton Polo Tee', 'T-Shirts', 'M', 'White', 3200.00, 10, 'Ground Floor - Zone 2', 'BC-STY1002-M-WHT-KDY', 1),
(2, 2, 'STY-1004', 'Adidas', 'Training Hoodie', 'Hoodies', 'L', 'Grey', 8900.00, 6, 'First Floor - Zone 1', 'BC-STY1004-L-GRY-KDY', 1);

INSERT INTO customers (branch_id, full_name, phone, email, loyalty_points) VALUES
(1, 'John Silva', '0711111111', 'john@email.com', 10),
(1, 'Jane Perera', '0722222222', 'jane@email.com', 5);
