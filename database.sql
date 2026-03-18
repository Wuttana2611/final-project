-- Restaurant QR Code Ordering System Database
-- MySQL 8.1+

CREATE DATABASE IF NOT EXISTS restaurant_qrcode CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_qrcode;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'chef', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tables Information
CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) UNIQUE NOT NULL,
    qr_code VARCHAR(255) NULL,
    session_token VARCHAR(100) NULL,
    status ENUM('available', 'occupied', 'reserved') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Menu Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Menu Items
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    session_token VARCHAR(100) NOT NULL,
    customer_name VARCHAR(100) NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'preparing', 'ready', 'served', 'completed', 'cancelled') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Order Items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    special_request TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert Default Admin User (password: password)
INSERT INTO users (username, password, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin'),
('chef', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พ่อครัว', 'chef');

-- Insert Default Categories
INSERT INTO categories (name, description, display_order) VALUES
('อาหารจานหลัก', 'เมนูอาหารจานหลักและข้าว', 1),
('ของทานเล่น', 'เมนูทานเล่นและเครื่องเคียง', 2),
('เครื่องดื่ม', 'เครื่องดื่มทุกประเภท', 3),
('ของหวาน', 'เมนูขนมและของหวาน', 4);

-- Insert Sample Menu Items
INSERT INTO menu_items (category_id, name, description, price, image, is_available) VALUES
(1, 'ข้าวผัดกุ้ง', 'ข้าวผัดกุ้งสดใหม่ รสชาติเข้มข้น', 120.00, 'khao-pad-kung.jpg', TRUE),
(1, 'ผัดกะเพราหมูกรอบ', 'หมูกรอบผัดกะเพราใบกะเพราสด', 100.00, 'pad-kaprao.jpg', TRUE),
(1, 'ต้มยำกุ้ง', 'ต้มยำกุ้งน้ำใส รสชาติเปรี้ยวจี๊ด', 150.00, 'tom-yum.jpg', TRUE),
(2, 'ปอเปี๊ยะทอด', 'ปอเปี๊ยะทอดกรอบสอดไส้หมูและผัก', 80.00, 'spring-roll.jpg', TRUE),
(2, 'ทอดมันปลา', 'ทอดมันปลากรายสูตรพิเศษ', 90.00, 'tod-mun.jpg', TRUE),
(3, 'น้ำส้มคั้น', 'น้ำส้มคั้นสดใหม่ 100%', 40.00, 'orange-juice.jpg', TRUE),
(3, 'กาแฟเย็น', 'กาแฟเย็นหอมกรุ่น', 50.00, 'iced-coffee.jpg', TRUE),
(3, 'ชาเขียวนมสด', 'ชาเขียวนมสดชั้นดี', 55.00, 'green-tea.jpg', TRUE),
(4, 'ไอศกรีมวานิลลา', 'ไอศกรีมวานิลลาเนื้อเนียน', 45.00, 'ice-cream.jpg', TRUE),
(4, 'ขนมฟักทอง', 'ขนมฟักทองหวานมัน', 35.00, 'kanom-fak.jpg', TRUE);

-- Insert Sample Tables (10 tables)
INSERT INTO tables (table_number, status) VALUES
('T01', 'available'),
('T02', 'available'),
('T03', 'available'),
('T04', 'available'),
('T05', 'available'),
('T06', 'available'),
('T07', 'available'),
('T08', 'available'),
('T09', 'available'),
('T10', 'available');
