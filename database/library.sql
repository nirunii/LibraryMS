-- LibraryMS v2 database. Import this file in phpMyAdmin (Import tab).
-- Adds renewals, a holds queue, fine payments, settings and an audit log to the original schema.
CREATE DATABASE IF NOT EXISTS library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_db;
SET time_zone = '+05:30';

DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NULL,
  role ENUM('admin','librarian','member') NOT NULL DEFAULT 'member',
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  isbn VARCHAR(20) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  author VARCHAR(150) NOT NULL,
  category VARCHAR(80) NOT NULL,
  copies_total INT NOT NULL DEFAULT 1,
  copies_available INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  user_id INT NOT NULL,
  borrowed_at DATETIME NOT NULL,
  due_date DATE NOT NULL,
  returned_at DATETIME NULL,
  fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  renewals INT NOT NULL DEFAULT 0,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE RESTRICT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Holds queue: active = waiting in line, ready = a copy is held for pickup until expires_on
CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  user_id INT NOT NULL,
  reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('active','ready','fulfilled','cancelled','expired') NOT NULL DEFAULT 'active',
  ready_at DATETIME NULL,
  expires_on DATE NULL,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Fine payments (a waived fine is stored as a payment with method 'waived')
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method ENUM('cash','card','online','waived') NOT NULL DEFAULT 'cash',
  note VARCHAR(200) NULL,
  received_by INT NULL,
  paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE RESTRICT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  user_name VARCHAR(100) NOT NULL,
  action VARCHAR(50) NOT NULL,
  details VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- SAMPLE DATA ---------------------------------------------------------
-- Passwords are bcrypt hashes (see README.md for the sample accounts), so login works straight after import.
INSERT INTO users (id, name, email, password, phone, role) VALUES
(1, 'System Admin',    'admin@library.com',     '$2y$12$tJ1yt4xH.dcW7Zt0uCfUzewVMtX6X77rIDelgs.HfYJcumK0D9AWu', '0771000001', 'admin'),
(2, 'Lakmal Librarian','librarian@library.com', '$2y$12$Hpsr70meDLFae0RaPDBbi.K9FPcm3shrb9Q7f0iOMqCVKUWBKW2zS', '0771000002', 'librarian'),
(3, 'Nimal Perera',    'nimal@mail.com',        '$2y$12$19U0/BzWHsW/8f9RAZkGPOWbrPm76qJGoXiywQw3YN/iXz1WMjRu.', '0771000003', 'member'),
(4, 'Kavindi Silva',   'kavindi@mail.com',      '$2y$12$sbio8VFasTgAKOzBXbbbuuam7CggrQkELcS.qoZ6IJZWjfmWbd4Xy', '0771000004', 'member'),
(5, 'Ruwan Fernando',  'ruwan@mail.com',        '$2y$12$sYbFJ1H3uLkst7HAxLjTVeJs8Z2lzrUPWbQTSYsxbKZ5v8PKWLgY2', '0771000005', 'member');

INSERT INTO settings (setting_key, setting_value) VALUES
('loan_days', '14'), ('fine_per_day', '50'), ('max_loans', '3'), ('max_renewals', '2'),
('renew_days', '7'), ('hold_pickup_days', '3'), ('fine_block_limit', '500');

INSERT INTO books (id, isbn, title, author, category, copies_total, copies_available) VALUES
(1, '9780132350884', 'Clean Code', 'Robert C. Martin', 'Programming', 3, 2),
(2, '9780201633610', 'Design Patterns', 'Erich Gamma', 'Programming', 1, 0),
(3, '9780735211292', 'Atomic Habits', 'James Clear', 'Self-help', 2, 1),
(4, '9780743273565', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction', 2, 2),
(5, '9780061120084', 'To Kill a Mockingbird', 'Harper Lee', 'Fiction', 2, 2),
(6, '9780262033848', 'Introduction to Algorithms', 'Thomas Cormen', 'Computer Science', 2, 2),
(7, '9780134494166', 'Clean Architecture', 'Robert C. Martin', 'Programming', 1, 1),
(8, '9780062316110', 'Sapiens', 'Yuval Noah Harari', 'History', 2, 2),
(9, '9780135957059', 'The Pragmatic Programmer', 'Andrew Hunt', 'Programming', 2, 2),
(10, '9780078022159', 'Database System Concepts', 'Abraham Silberschatz', 'Computer Science', 3, 3);

-- Active loans (1 on time, 2 overdue)
INSERT INTO loans (book_id, user_id, borrowed_at, due_date, returned_at, fine_amount) VALUES
(1, 3, DATE_SUB(NOW(), INTERVAL 5 DAY),  DATE_ADD(CURDATE(), INTERVAL 9 DAY),  NULL, 0),
(2, 4, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 6 DAY),  NULL, 0),
(3, 5, DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 4 DAY),  NULL, 0);

-- Past returned loans (feed the monthly chart and the most-borrowed report).
-- Loan 9 (fine Rs. 100) is fully paid, loan 13 (Rs. 150) is unpaid, loan 16 (Rs. 100) is part paid.
INSERT INTO loans (book_id, user_id, borrowed_at, due_date, returned_at, fine_amount) VALUES
(1, 3, DATE_SUB(NOW(), INTERVAL 150 DAY), DATE_SUB(CURDATE(), INTERVAL 136 DAY), DATE_SUB(NOW(), INTERVAL 142 DAY), 0),
(1, 4, DATE_SUB(NOW(), INTERVAL 142 DAY), DATE_SUB(CURDATE(), INTERVAL 128 DAY), DATE_SUB(NOW(), INTERVAL 132 DAY), 0),
(3, 5, DATE_SUB(NOW(), INTERVAL 130 DAY), DATE_SUB(CURDATE(), INTERVAL 116 DAY), DATE_SUB(NOW(), INTERVAL 118 DAY), 0),
(2, 3, DATE_SUB(NOW(), INTERVAL 121 DAY), DATE_SUB(CURDATE(), INTERVAL 107 DAY), DATE_SUB(NOW(), INTERVAL 112 DAY), 0),
(1, 4, DATE_SUB(NOW(), INTERVAL 110 DAY), DATE_SUB(CURDATE(), INTERVAL 96 DAY), DATE_SUB(NOW(), INTERVAL 102 DAY), 0),
(9, 5, DATE_SUB(NOW(), INTERVAL 98 DAY), DATE_SUB(CURDATE(), INTERVAL 84 DAY), DATE_SUB(NOW(), INTERVAL 82 DAY), 100),
(6, 3, DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(CURDATE(), INTERVAL 76 DAY), DATE_SUB(NOW(), INTERVAL 78 DAY), 0),
(1, 4, DATE_SUB(NOW(), INTERVAL 81 DAY), DATE_SUB(CURDATE(), INTERVAL 67 DAY), DATE_SUB(NOW(), INTERVAL 72 DAY), 0),
(3, 5, DATE_SUB(NOW(), INTERVAL 72 DAY), DATE_SUB(CURDATE(), INTERVAL 58 DAY), DATE_SUB(NOW(), INTERVAL 64 DAY), 0),
(2, 3, DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 46 DAY), DATE_SUB(NOW(), INTERVAL 43 DAY), 150),
(5, 4, DATE_SUB(NOW(), INTERVAL 52 DAY), DATE_SUB(CURDATE(), INTERVAL 38 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY), 0),
(8, 5, DATE_SUB(NOW(), INTERVAL 44 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY), 0),
(1, 3, DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 21 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY), 100),
(4, 4, DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY), 0),
(10, 5, DATE_SUB(NOW(), INTERVAL 22 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 0),
(9, 3, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), 0),
(7, 4, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL -4 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 0);

INSERT INTO reservations (book_id, user_id, status) VALUES (2, 3, 'active');

INSERT INTO payments (loan_id, user_id, amount, method, note, received_by, paid_at) VALUES
(9,  5, 100.00, 'cash', 'Paid in full at the desk', 2, DATE_SUB(NOW(), INTERVAL 80 DAY)),
(16, 3,  50.00, 'cash', 'Part payment',             2, DATE_SUB(NOW(), INTERVAL 18 DAY));

INSERT INTO audit_log (user_id, user_name, action, details) VALUES
(NULL, 'System', 'database.import', 'Sample data imported');
