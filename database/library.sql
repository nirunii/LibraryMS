-- LibraryMS database. Import this file in phpMyAdmin (Import tab).
CREATE DATABASE IF NOT EXISTS library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_db;
SET time_zone = '+05:30';

DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','librarian','member') NOT NULL DEFAULT 'member',
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
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE RESTRICT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  user_id INT NOT NULL,
  reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('active','fulfilled','cancelled') NOT NULL DEFAULT 'active',
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- SAMPLE DATA ---------------------------------------------------------
-- Passwords are bcrypt hashes (see the sample accounts in README.md), so you can log in straight after importing.
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'System Admin',    'admin@library.com',     '$2y$12$tJ1yt4xH.dcW7Zt0uCfUzewVMtX6X77rIDelgs.HfYJcumK0D9AWu', 'admin'),
(2, 'Lakmal Librarian','librarian@library.com', '$2y$12$Hpsr70meDLFae0RaPDBbi.K9FPcm3shrb9Q7f0iOMqCVKUWBKW2zS', 'librarian'),
(3, 'Nimal Perera',    'nimal@mail.com',        '$2y$12$19U0/BzWHsW/8f9RAZkGPOWbrPm76qJGoXiywQw3YN/iXz1WMjRu.', 'member'),
(4, 'Kavindi Silva',   'kavindi@mail.com',      '$2y$12$sbio8VFasTgAKOzBXbbbuuam7CggrQkELcS.qoZ6IJZWjfmWbd4Xy', 'member'),
(5, 'Ruwan Fernando',  'ruwan@mail.com',        '$2y$12$sYbFJ1H3uLkst7HAxLjTVeJs8Z2lzrUPWbQTSYsxbKZ5v8PKWLgY2', 'member');

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

-- Past returned loans (feeds the monthly chart and the most-borrowed report)
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
(2, 3, DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 46 DAY), DATE_SUB(NOW(), INTERVAL 50 DAY), 0),
(5, 4, DATE_SUB(NOW(), INTERVAL 52 DAY), DATE_SUB(CURDATE(), INTERVAL 38 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY), 0),
(8, 5, DATE_SUB(NOW(), INTERVAL 44 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY), 0),
(1, 3, DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 21 DAY), DATE_SUB(NOW(), INTERVAL 27 DAY), 0),
(4, 4, DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY), 0),
(10, 5, DATE_SUB(NOW(), INTERVAL 22 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 0),
(9, 3, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), 0),
(7, 4, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL -4 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 0);

INSERT INTO reservations (book_id, user_id, status) VALUES (2, 3, 'active');
