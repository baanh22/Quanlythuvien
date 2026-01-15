DROP DATABASE IF EXISTS quanlythuvien;
CREATE DATABASE IF NOT EXISTS quanlythuvien CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanlythuvien;

-- Bảng Danh mục
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Bảng Nhà xuất bản
CREATE TABLE publishers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    contact VARCHAR(100)
);

-- Bảng Sách
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    category_id INT,
    publisher_id INT,
    total_quantity INT DEFAULT 0,
    available_quantity INT DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'hidden') DEFAULT 'active',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE SET NULL
);

-- Bảng Sinh viên
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mssv VARCHAR(20) NOT NULL UNIQUE,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    card_expiry_date DATE NOT NULL,
    status ENUM('active', 'locked') DEFAULT 'active'
);

-- Bảng Tài khoản (Người dùng hệ thống)
CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    last_login DATETIME
);

-- Bảng Phiếu mượn
CREATE TABLE borrow_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    status ENUM('borrowed', 'returned') DEFAULT 'borrowed',
    created_by INT,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (created_by) REFERENCES accounts(id)
);

-- Bảng Phạt
CREATE TABLE fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrow_record_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (borrow_record_id) REFERENCES borrow_records(id)
);

-- Bảng Nhật ký hệ thống
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT,
    action TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL
);

-- Dữ liệu mẫu
INSERT INTO categories (name) VALUES ('Công nghệ thông tin'), ('Kinh tế'), ('Văn học'), ('Ngoại ngữ');

INSERT INTO publishers (name, address, contact) VALUES 
('NXB Trẻ', '161B Lý Chính Thắng, Q3, TP.HCM', '02839316289'),
('NXB Kim Đồng', '55 Quang Trung, Hà Nội', '02439434730');

INSERT INTO accounts (username, password, fullname, role) VALUES 
('admin', '$2y$10$56YXFTLp5WMCtTOkxnUc8.Y/bukjGHYF37/K4m/llWLNNUbGk8Yly', 'Quản trị viên', 'admin'), -- pass: admin123
('thuthu', '$2y$10$56YXFTLp5WMCtTOkxnUc8.Y/bukjGHYF37/K4m/llWLNNUbGk8Yly', 'Thủ thư 1', 'staff'); -- pass: admin123

INSERT INTO students (mssv, fullname, card_expiry_date) VALUES 
('SV001', 'Nguyễn Văn A', '2025-12-31'),
('SV002', 'Trần Thị B', '2024-12-31');
