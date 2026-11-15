ALTER TABLE students
    ADD COLUMN password_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER password_hash;

ALTER TABLE teachers
    ADD COLUMN password_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER password_hash;

CREATE TABLE password_reset_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('student', 'teacher') NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    reason VARCHAR(1000) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_requests_status (status, created_at),
    INDEX idx_password_requests_user (user_type, user_id)
);
