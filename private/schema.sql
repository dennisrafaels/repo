-- Activity Calendar System Database
-- Compatible with MariaDB / PostgreSQL

CREATE DATABASE IF NOT EXISTS activity_calendar;
USE activity_calendar;

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) NOT NULL DEFAULT '#4CAF50',
    icon VARCHAR(10) NOT NULL DEFAULT '📌'
);

-- Activities Table
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    location VARCHAR(100),
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    max_participants INT DEFAULT NULL,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- RSVPs Table
CREATE TABLE IF NOT EXISTS rsvps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    participant_name VARCHAR(50) NOT NULL,
    participant_email VARCHAR(100),
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rsvp (activity_id, participant_name),
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
);

-- Seed: Categories
INSERT INTO categories (name, color, icon) VALUES
('Sports', '#FF5722', '⚽'),
('Study', '#2196F3', '📚'),
('Social', '#9C27B0', '🎉'),
('Arts', '#FF9800', '🎨'),
('Tech', '#00BCD4', '💻');

-- Seed: Sample Activities
INSERT INTO activities (title, description, category_id, location, start_datetime, end_datetime, max_participants, created_by) VALUES
('Basketball Pickup Game', 'Casual 3v3 basketball, all skill levels welcome!', 1, 'Campus Court B', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 1 DAY + INTERVAL 2 HOUR), 12, 'Alex'),
('Final Exam Study Group', 'Group study session for Data Structures exam', 2, 'Library Room 3', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY + INTERVAL 3 HOUR), 8, 'Sam'),
('Movie Night', 'Watching Interstellar with popcorn!', 3, 'Common Room 1F', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY + INTERVAL 3 HOUR), 20, 'Jordan'),
('Web Dev Workshop', 'Learn HTML/CSS/JS basics from scratch', 5, 'Lab 204', DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_ADD(NOW(), INTERVAL 4 DAY + INTERVAL 2 HOUR), 15, 'Taylor'),
('Painting Session', 'Bring your own canvas, paint provided!', 4, 'Art Room 101', DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY + INTERVAL 2 HOUR), 10, 'Riley');
