-- Create database
CREATE DATABASE IF NOT EXISTS library_management;
USE library_management;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_approved BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE users
ADD COLUMN mobile VARCHAR(15) AFTER email,
ADD COLUMN library_id VARCHAR(20) AFTER mobile,
ADD COLUMN membership_type VARCHAR(50) DEFAULT 'Basic' AFTER library_id,
ADD COLUMN membership_date DATE AFTER membership_type,
ADD COLUMN last_login DATETIME AFTER membership_date,
ADD COLUMN profile_pic VARCHAR(255) AFTER last_login;

ALTER TABLE users 
add column updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN membership_level ENUM('basic', 'premium', 'gold') DEFAULT 'basic';

ALTER TABLE users 
ADD COLUMN is_active BOOLEAN DEFAULT TRUE,
ADD COLUMN last_activity TIMESTAMP NULL,
ADD COLUMN request_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL;

-- For better admin management
ALTER TABLE users
MODIFY COLUMN user_type ENUM('user', 'admin', 'super_admin') NOT NULL DEFAULT 'user';

-- Admin requests table
CREATE TABLE admin_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO users (full_name, email, username, password, user_type, is_approved)
VALUES 
    ('Library User', 'user@library.com', 'libraryuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', TRUE),
    ('Library Admin', 'admin@library.com', 'libraryadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);-- Add owner table
CREATE TABLE IF NOT EXISTS owners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE
);

-- Add status and token to admin_requests
ALTER TABLE admin_requests
ADD COLUMN token VARCHAR(255),
ADD COLUMN approved_at TIMESTAMP NULL;

-- Insert default owner (change credentials in production)
INSERT INTO owners (username, password, email) 
VALUES ('library_owner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner@library.com');

CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(20),
    genre VARCHAR(100),
    published_year INT,
    edition VARCHAR(20),
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    cover_image VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE books
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE books ADD COLUMN category VARCHAR(100) AFTER isbn;
ALTER TABLE books ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER category;
ALTER TABLE books ADD COLUMN available_quantity INT NOT NULL DEFAULT 1 AFTER quantity;
ALTER TABLE books ADD COLUMN description TEXT AFTER available_quantity;


CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  reservation_date DATETIME NOT NULL,
  expiry_date DATETIME NOT NULL,
  status ENUM('pending', 'active', 'expired', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE reservations
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
-- Create borrowings table if it doesn't exist
CREATE TABLE IF NOT EXISTS borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATETIME NOT NULL,
    due_date DATETIME NOT NULL,
    return_date DATETIME NULL,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Add index for better performance
CREATE INDEX idx_borrowings_user ON borrowings(user_id);
CREATE INDEX idx_borrowings_book ON borrowings(book_id);
CREATE INDEX idx_borrowings_status ON borrowings(status);

CREATE TABLE fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    borrowing_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    issued_date DATETIME NOT NULL,
    status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    payment_date DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (borrowing_id) REFERENCES borrowings(id)
);
alter table fines
add column created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
add column updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fine_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_date DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (fine_id) REFERENCES fines(id)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('info', 'warning', 'urgent') DEFAULT 'info',
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    action_text VARCHAR(100) NULL,
    action_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id),
    UNIQUE KEY unique_review (user_id, book_id)
);

ALTER TABLE reviews
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE ebooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    file_format ENUM('PDF', 'EPUB', 'MOBI') NOT NULL,
    file_size VARCHAR(20) NOT NULL,
    access_level ENUM('all', 'basic', 'premium', 'gold') DEFAULT 'all',
    download_count INT DEFAULT 0,
    FOREIGN KEY (book_id) REFERENCES books(id)
);

CREATE TABLE download_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ebook_id INT NOT NULL,
    download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (ebook_id) REFERENCES ebooks(id)
);

-- Wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id),
    UNIQUE KEY (user_id, book_id)
);
select * from users;

-- Insert the user Manolashini V
INSERT INTO users (full_name, email, mobile, username, password, user_type, is_approved, 
                   library_id, membership_type, membership_date, membership_level, is_active)
VALUES ('Manolashini V', 'manolashini94@gmail.com', '1234567890', 'mano', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'user', TRUE, 'LIB123456', 'premium', CURDATE(), 'premium', TRUE);
-- Get the user ID

-- Update password for user 'mano'
UPDATE users
SET password = '$2y$10$T7FT5NsTr/IEkyYcn0R1veG2sNDkkQ9HylLzTQjl6Ex9pFZBvAoWS'
WHERE username = 'mano';

SET @user_id = LAST_INSERT_ID();

-- Insert Data Structures and Algorithms book
INSERT INTO books (title, author, isbn, genre, published_year, edition, 
                   total_copies, available_copies, cover_image, description)
VALUES ('Data Structures and Algorithms', 'Mark Allen Weiss', '9780132576277', 
        'Computer Science', 2018, '3rd', 5, 5, 
        'https://example.com/dsa_front.jpg', 
        'Comprehensive coverage of fundamental data structures and algorithms with clear explanations and examples.');

-- Get the book ID
SET @dsa_book_id = LAST_INSERT_ID();

-- Insert Operating Systems: Design and Implementation
INSERT INTO books (title, author, isbn, genre, published_year, edition, 
                   total_copies, available_copies, cover_image, description)
VALUES ('Operating Systems: Design and Implementation', 'Andrew S. Tanenbaum', '9780131429383', 
        'Operating Systems', 2019, '2nd', 3, 3, 
        'https://example.com/os_design_implementation.jpg', 
        'Comprehensive textbook covering the principles of operating systems design and implementation, with a focus on MINIX.');

-- Get the book ID
SET @os_book_id = LAST_INSERT_ID();

-- Insert Database Management Systems
INSERT INTO books (title, author, isbn, genre, published_year, edition, 
                   total_copies, available_copies, cover_image, description)
VALUES ('Database Management Systems', 'Raghu Ramakrishnan', '9780072465631', 
        'Database', 2017, '5th', 4, 4, 
        'https://example.com/dbms_front.jpg', 
        'The most comprehensive and up-to-date coverage of database principles available, with a focus on database design and use.');

-- Get the book ID
SET @dbms_book_id = LAST_INSERT_ID();

-- Add books to Manolashini's wishlist
INSERT INTO wishlist (user_id, book_id, added_date)
VALUES 
    (@user_id, @os_book_id, NOW()),
    (@user_id, @dbms_book_id, NOW());

-- Add reviews for these books from Manolashini
INSERT INTO reviews (user_id, book_id, rating, review_text)
VALUES 
    (@user_id, @dsa_book_id, 5, 'Excellent resource for understanding core algorithms with practical implementations.'),
    (@user_id, @os_book_id, 5, 'Excellent book on OS concepts with practical MINIX examples. Highly recommended for serious students.'),
    (@user_id, @dbms_book_id, 4, 'Comprehensive coverage of database systems, though some sections could be more beginner-friendly.');

-- Add ebook versions
INSERT INTO ebooks (book_id, file_url, file_format, file_size, access_level)
VALUES 
    (@dsa_book_id, '/ebooks/dsa_3rd.pdf', 'PDF', '4.5MB', 'premium'),
    (@os_book_id, '/ebooks/os_design.pdf', 'PDF', '5.2MB', 'premium'),
    (@dbms_book_id, '/ebooks/dbms_5th.epub', 'EPUB', '3.8MB', 'premium');

-- Create a borrowing record for Data Structures book
INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, return_date, status)
VALUES (@user_id, @dsa_book_id, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 9 DAY), NULL, 'borrowed');

-- Get the borrowing ID
SET @borrowing_id = LAST_INSERT_ID();

-- Update available copies for borrowed book
UPDATE books SET available_copies = available_copies - 1 WHERE id = @dsa_book_id;

-- Create a reservation for OS book
INSERT INTO reservations (user_id, book_id, reservation_date, expiry_date, status)
VALUES (@user_id, @os_book_id, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending');

-- Create a fine for potential late return
INSERT INTO fines (user_id, borrowing_id, amount, reason, issued_date, status)
VALUES (@user_id, @borrowing_id, 2.50, 'Potential late return fee', NOW(), 'unpaid');

-- Get the fine ID
SET @fine_id = LAST_INSERT_ID();

-- Create a notification for the user
INSERT INTO notifications (user_id, type, message, is_read, action_text, action_url)
VALUES (@user_id, 'warning', 'Your borrowed book "Data Structures and Algorithms" is due in 9 days.', 
        FALSE, 'View Details', '/my-borrowings');

-- Create admin user
INSERT INTO users (full_name, email, username, password, user_type, is_approved)
VALUES ('Library Admin', 'admin@library.com', 'libadmin', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'admin', TRUE);

-- Create sample admin request (for demonstration)
INSERT INTO admin_requests (user_id, full_name, email, position, reason, status)
VALUES (@user_id, 'Manolashini V', 'manolashini94@gmail.com', 'Assistant Librarian', 
        'I would like to help manage the library collection and assist other users.', 'pending');

-- Create download history record
INSERT INTO download_history (user_id, ebook_id, download_date)
VALUES (@user_id, (SELECT id FROM ebooks WHERE book_id = @dsa_book_id LIMIT 1), NOW());

-- Insert Artificial Intelligence: A Modern Approach
INSERT INTO books (title, author, isbn, genre, published_year, edition, 
                   total_copies, available_copies, cover_image, description)
VALUES ('Artificial Intelligence: A Modern Approach', 'Stuart Russell', '9780134610993', 
        'AI', 2018, '4th', 4, 4, 
        'https://example.com/ai_modern_approach.jpg', 
        'The authoritative, most-used AI textbook that provides a comprehensive introduction to the theory and practice of artificial intelligence.');

-- Get the book ID
SET @ai_book_id = LAST_INSERT_ID();

-- Insert Fundamentals of Database Systems
INSERT INTO books (title, author, isbn, genre, published_year, edition, 
                   total_copies, available_copies, cover_image, description)
VALUES ('Fundamentals of Database Systems', 'Ramez Elmasri', '9780133970777', 
        'Database', 2020, '7th', 3, 3, 
        'https://example.com/fundamentals_dbms.jpg', 
        'Comprehensive textbook that combines clear explanations of theory and design with practical coverage of database implementation.');

-- Get the book ID
SET @db_book_id = LAST_INSERT_ID();

-- Add these books to meenashalini's wishlist
INSERT INTO wishlist (user_id, book_id, added_date)
VALUES 
    (7, @ai_book_id, NOW()),
    (7, @db_book_id, NOW());

-- Add reviews from meenashalini
INSERT INTO reviews (user_id, book_id, rating, review_text)
VALUES 
    (7, @ai_book_id, 5, 'The definitive AI textbook that covers everything from basic concepts to advanced topics. Essential for any CS student.'),
    (7, @db_book_id, 4, 'Excellent coverage of database fundamentals with clear examples. The new edition includes valuable updates on NoSQL systems.');

-- Add ebook versions
INSERT INTO ebooks (book_id, file_url, file_format, file_size, access_level)
VALUES 
    (@ai_book_id, '/ebooks/ai_modern_4th.pdf', 'PDF', '8.1MB', 'premium'),
    (@db_book_id, '/ebooks/fundamentals_db_7th.epub', 'EPUB', '6.5MB', 'basic');

-- Create a borrowing record for AI book
INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, return_date, status)
VALUES (7, @ai_book_id, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), NULL, 'borrowed');

-- Update available copies for borrowed book
UPDATE books SET available_copies = available_copies - 1 WHERE id = @ai_book_id;

-- Get the borrowing ID
SET @borrowing_id = LAST_INSERT_ID();

-- Create a reservation for Database book
INSERT INTO reservations (user_id, book_id, reservation_date, expiry_date, status)
VALUES (7, @db_book_id, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending');

-- Create a notification for the reservation
INSERT INTO notifications (user_id, type, message, is_read, action_text, action_url)
VALUES (7, 'info', 'Your reservation for "Fundamentals of Database Systems" is pending approval.', 
        FALSE, 'View Status', '/my-reservations');

-- Create download history record for the ebook
INSERT INTO download_history (user_id, ebook_id, download_date)
VALUES (7, (SELECT id FROM ebooks WHERE book_id = @db_book_id LIMIT 1), NOW());