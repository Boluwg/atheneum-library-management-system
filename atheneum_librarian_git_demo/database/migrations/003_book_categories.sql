ALTER TABLE books
    ADD COLUMN category ENUM('fiction', 'non_fiction', 'academic', 'other')
    NOT NULL DEFAULT 'other' AFTER author,
    ADD INDEX idx_books_category (category);
