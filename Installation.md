# Installation Guide

This guide explains how to install and run ActivityHub on a Raspberry Pi Zero 2W running DietPi (or any Debian-based Linux system).

## Requirements

- Raspberry Pi Zero 2W (or any Debian-based Linux)
- Internet connection (for initial setup only)
- Git installed on your local machine

## Step 1 — Update the System

```bash
sudo apt update && sudo apt upgrade -y
```

## Step 2 — Install Apache, PHP, and MariaDB

```bash
sudo apt install apache2 php libapache2-mod-php php-mysql mariadb-server git -y
```

## Step 3 — Start and Enable Services

```bash
sudo mkdir -p /var/log/apache2
sudo systemctl start apache2
sudo systemctl enable apache2
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

Verify both are running:

```bash
sudo systemctl status apache2
sudo systemctl status mariadb
```

## Step 4 — Set Up the Database

Log into MariaDB:

```bash
sudo mysql -u root
```

Run the following SQL commands:

```sql
CREATE DATABASE activity_calendar;
CREATE USER 'caluser'@'localhost' IDENTIFIED BY 'calpass123';
GRANT ALL PRIVILEGES ON activity_calendar.* TO 'caluser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 5 — Clone the Repository

```bash
cd /var/www/html
sudo git init
sudo git remote add origin https://github.com/dennisrafaels/repo
sudo git pull origin main
```

## Step 6 — Import the Database Schema

```bash
sudo mysql -u root activity_calendar < /var/www/html/sql/schema.sql
```

Then insert the sample activity data:

```bash
sudo mysql -u root activity_calendar -e "
INSERT INTO activities (title, description, category_id, location, start_datetime, end_datetime, max_participants, created_by) VALUES
('Basketball Pickup Game', 'Casual 3v3 basketball, all skill levels welcome!', 1, 'Campus Court B', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 1 DAY), INTERVAL 2 HOUR), 12, 'Alex'),
('Final Exam Study Group', 'Group study session for Data Structures exam', 2, 'Library Room 3', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 2 DAY), INTERVAL 3 HOUR), 8, 'Sam'),
('Movie Night', 'Watching Interstellar with popcorn!', 3, 'Common Room 1F', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 3 DAY), INTERVAL 3 HOUR), 20, 'Jordan'),
('Web Dev Workshop', 'Learn HTML/CSS/JS basics from scratch', 5, 'Lab 204', DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 4 DAY), INTERVAL 2 HOUR), 15, 'Taylor'),
('Painting Session', 'Bring your own canvas, paint provided!', 4, 'Art Room 101', DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 5 DAY), INTERVAL 2 HOUR), 10, 'Riley');
"
```

## Step 7 — Configure Database Credentials

```bash
sudo sed -i "s/define('DB_USER', 'root')/define('DB_USER', 'caluser')/" /var/www/html/php/config.php
sudo sed -i "s/define('DB_PASS', '')/define('DB_PASS', 'calpass123')/" /var/www/html/php/config.php
```

## Step 8 — Verify Installation

Find your Pi's IP address:

```bash
hostname -I
```

Open a browser and go to:

```
http://<your-pi-ip>/index.php
```

You should see the ActivityHub calendar with sample activities.

## Troubleshooting

**Apache won't start:**
```bash
sudo mkdir -p /var/log/apache2
sudo systemctl restart apache2
```

**Database connection error:**
- Check credentials in `/var/www/html/php/config.php`
- Verify MariaDB is running: `sudo systemctl status mariadb`

**Permission issues:**
```bash
sudo chown -R www-data:www-data /var/www/html
```
