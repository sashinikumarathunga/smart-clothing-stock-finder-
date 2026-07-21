CREATE DATABASE IF NOT EXISTS smart_stock_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_stock_finder;

CREATE TABLE branches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    location VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    loyalty_spend_per_point DECIMAL(10, 2) NOT NULL DEFAULT 5000.00,
    loyalty_point_value DECIMAL(10, 2) NOT NULL DEFAULT 100.00,
    loyalty_min_redeem INT UNSIGNED NOT NULL DEFAULT 5,
    return_period_days INT UNSIGNED NOT NULL DEFAULT 7,
    low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NULL,
    role ENUM('owner', 'branch_admin', 'storekeeper', 'sales_assistant', 'cashier') NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    contact_person VARCHAR(120) NOT NULL DEFAULT '',
    phone VARCHAR(20) NOT NULL DEFAULT '',
    email VARCHAR(120) NOT NULL DEFAULT '',
    address VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NULL,
    style_code VARCHAR(50) NOT NULL,
    brand VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'Clothing',
    size VARCHAR(20) NOT NULL,
    color VARCHAR(40) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    location_in_store VARCHAR(120) NOT NULL DEFAULT '',
    barcode VARCHAR(80) NOT NULL UNIQUE,
    low_stock_alert_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    INDEX idx_products_style (style_code),
    INDEX idx_products_branch (branch_id)
);

CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(120) NOT NULL DEFAULT '',
    loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_customers_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    UNIQUE KEY uq_customer_phone_branch (branch_id, phone)
);

CREATE TABLE reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    sales_assistant_id INT UNSIGNED NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    qty INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('active', 'expired', 'cancelled', 'completed') NOT NULL DEFAULT 'active',
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservations_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_reservations_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    CONSTRAINT fk_reservations_assistant FOREIGN KEY (sales_assistant_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    cashier_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card') NOT NULL,
    loyalty_points_earned INT UNSIGNED NOT NULL DEFAULT 0,
    loyalty_points_redeemed INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_cashier FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    status ENUM('pending', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at DATETIME NULL,
    CONSTRAINT fk_po_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_po_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE purchase_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty_ordered INT UNSIGNED NOT NULL,
    CONSTRAINT fk_poi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_poi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE returns_exchanges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    sale_item_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    processed_by INT UNSIGNED NOT NULL,
    type ENUM('return', 'exchange') NOT NULL,
    qty INT UNSIGNED NOT NULL,
    reason VARCHAR(255) NOT NULL DEFAULT '',
    exchange_product_id INT UNSIGNED NULL,
    price_difference DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_re_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
    CONSTRAINT fk_re_sale_item FOREIGN KEY (sale_item_id) REFERENCES sale_items(id) ON DELETE RESTRICT,
    CONSTRAINT fk_re_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    CONSTRAINT fk_re_processed_by FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_re_exchange_product FOREIGN KEY (exchange_product_id) REFERENCES products(id) ON DELETE SET NULL
);

CREATE TABLE api_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_api_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_api_tokens_expires (expires_at)
);
