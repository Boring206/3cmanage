-- 3Cmanage Database Schema

-- Reset database if exists
DROP DATABASE IF EXISTS 3cmanage;
CREATE DATABASE 3cmanage DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE 3cmanage;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('customer', 'store_admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model_number VARCHAR(100) NOT NULL,
    specifications JSON,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255),
    default_warranty_months INT NOT NULL DEFAULT 12,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Addresses table
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recipient_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city VARCHAR(100) NOT NULL,
    street VARCHAR(255) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Taiwan',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    subtotal_amount DECIMAL(10, 2) NOT NULL,
    shipping_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'credit_card',
    transaction_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id)
);

-- Order Items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Warranties table
CREATE TABLE warranties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    serial_number VARCHAR(100) NOT NULL,
    purchase_date DATE NOT NULL,
    warranty_period_months INT NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('active', 'expired', 'voided') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Inventory Logs table
CREATE TABLE inventory_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT,
    change_in_quantity INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create default admin user
INSERT INTO users (username, email, password, name, role)
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'store_admin');
-- Password: password

-- Create sample categories
INSERT INTO products (name, description, category, brand, model_number, specifications, price, stock_quantity, image_url, default_warranty_months)
VALUES 
('iPhone 15 Pro', 'The latest iPhone with A17 Pro chip', 'Smartphone', 'Apple', 'iPhone15Pro', '{"screen": "6.1 inch", "memory": "8GB", "storage": "256GB", "color": "Titanium Blue"}', 32900, 50, 'https://example.com/images/iphone15pro.jpg', 12),
('Galaxy S24 Ultra', 'Samsung flagship with advanced camera', 'Smartphone', 'Samsung', 'SM-S928B', '{"screen": "6.8 inch", "memory": "12GB", "storage": "512GB", "color": "Green"}', 29900, 40, 'https://example.com/images/s24ultra.jpg', 12),
('MacBook Air M2', 'Ultra-thin notebook with Apple M2 chip', 'Laptop', 'Apple', 'MBA-M2-2023', '{"screen": "13.6 inch", "memory": "16GB", "storage": "512GB", "color": "Space Gray"}', 42900, 25, 'https://example.com/images/macbookair-m2.jpg', 24),
('ASUS ROG Zephyrus G14', 'Gaming laptop with AMD Ryzen 9', 'Laptop', 'ASUS', 'GA403XI', '{"screen": "14 inch", "memory": "32GB", "storage": "1TB", "color": "Eclipse Gray"}', 49900, 15, 'https://example.com/images/asus-rog-g14.jpg', 24);

-- Create inventory logs for the initial stock
INSERT INTO inventory_logs (product_id, user_id, change_in_quantity, reason) VALUES 
(1, 1, 50, 'Initial inventory'),
(2, 1, 40, 'Initial inventory'),
(3, 1, 25, 'Initial inventory'),
(4, 1, 15, 'Initial inventory');
