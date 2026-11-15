CREATE DATABASE IF NOT EXISTS atheneum_demo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE atheneum_demo;

CREATE TABLE admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB;

CREATE TABLE students (
  student_id VARCHAR(64) NOT NULL,
  name VARCHAR(150) NOT NULL,
  class VARCHAR(100) NOT NULL,
  section VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  password_version INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (student_id)
) ENGINE=InnoDB;

CREATE TABLE teachers (
  teacher_id VARCHAR(64) NOT NULL,
  name VARCHAR(150) NOT NULL,
  department VARCHAR(150) NOT NULL,
  designation VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  password_version INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (teacher_id)
) ENGINE=InnoDB;

CREATE TABLE books (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  category VARCHAR(32) NOT NULL DEFAULT 'other',
  accession_no VARCHAR(100) NOT NULL,
  rfid_tag VARCHAR(100) NOT NULL,
  status ENUM('available', 'issued', 'reserved') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_books_accession_no (accession_no),
  UNIQUE KEY uq_books_rfid_tag (rfid_tag),
  KEY idx_books_status (status),
  KEY idx_books_category (category)
) ENGINE=InnoDB;

CREATE TABLE issued_books (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id VARCHAR(64) NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE NOT NULL,
  return_date DATE DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_issued_student (student_id),
  KEY idx_issued_book_return (book_id, return_date),
  CONSTRAINT fk_issued_book FOREIGN KEY (book_id) REFERENCES books (id)
) ENGINE=InnoDB;

CREATE TABLE reservations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_id INT UNSIGNED NOT NULL,
  user_id VARCHAR(64) NOT NULL,
  reserve_date DATE NOT NULL,
  queue_position INT UNSIGNED NOT NULL,
  status ENUM('active', 'cancelled', 'fulfilled', 'ready') NOT NULL DEFAULT 'active',
  PRIMARY KEY (id),
  KEY idx_reservations_book_status (book_id, status),
  CONSTRAINT fk_reservation_book FOREIGN KEY (book_id) REFERENCES books (id)
) ENGINE=InnoDB;

CREATE TABLE password_reset_requests (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_type ENUM('student', 'teacher') NOT NULL,
  user_id VARCHAR(64) NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_password_requests_status (status)
) ENGINE=InnoDB;

INSERT INTO admins (username, password)
VALUES ('admin', '$2y$10$AeU0Ugv.A0XSJrJKDjXLFevTgTByFTz2tO20ATlPY7otFmpRed9pK');

INSERT INTO students (student_id, name, class, section, password_hash)
VALUES ('DEMO-001', 'Demo Student', 'Library Demo', 'A',
        '$2y$10$AeU0Ugv.A0XSJrJKDjXLFevTgTByFTz2tO20ATlPY7otFmpRed9pK');

INSERT INTO books (title, author, category, accession_no, rfid_tag, status)
VALUES ('Demo Book', 'Atheneum Example', 'fiction', 'DEMO-BOOK-001', 'DEMO-RFID-001', 'available');
