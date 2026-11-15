ALTER TABLE students
    ADD COLUMN password_hash VARCHAR(255) NULL AFTER section;

ALTER TABLE teachers
    ADD COLUMN password_hash VARCHAR(255) NULL AFTER designation;

CREATE INDEX idx_issued_student_active
    ON issued_books (student_id, return_date);

CREATE INDEX idx_issued_book_active
    ON issued_books (book_id, return_date);

CREATE INDEX idx_reservations_book_queue
    ON reservations (book_id, status, queue_position);

CREATE INDEX idx_reservations_user_status
    ON reservations (user_id, status);
