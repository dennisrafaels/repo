# Admin Guide

This guide covers how to configure, maintain, and troubleshoot ActivityHub on the Raspberry Pi Zero 2W.

## Server Configuration

### Apache Configuration

The Apache web server serves files from `/var/www/html/`. The main config file is at `/etc/apache2/apache2.conf`.

To restart Apache after any configuration change:
```bash
sudo systemctl restart apache2
```

To check Apache status:
```bash
sudo systemctl status apache2
```

Apache error logs are at:
```bash
sudo tail -f /var/log/apache2/error.log
```

### PHP Configuration

PHP configuration file is at `/etc/php/8.4/apache2/php.ini`.

To check the PHP version:
```bash
php -v
```

After editing `php.ini`, restart Apache:
```bash
sudo systemctl restart apache2
```

### Database Configuration

The database credentials are stored in:
```
/var/www/html/php/config.php
```

To edit credentials:
```bash
sudo nano /var/www/html/php/config.php
```

Default values:
| Setting | Value |
|---------|-------|
| Host | localhost |
| Database | activity_calendar |
| Username | caluser |
| Password | calpass123 |

## Database Maintenance

### Accessing the Database

```bash
sudo mysql -u root activity_calendar
```

### Useful SQL Commands

View all activities:
```sql
SELECT * FROM activities;
```

View all RSVPs:
```sql
SELECT * FROM rsvps;
```

View all categories:
```sql
SELECT * FROM categories;
```

Delete all RSVPs for a specific activity:
```sql
DELETE FROM rsvps WHERE activity_id = <id>;
```

### Backup the Database

To create a backup:
```bash
sudo mysqldump -u root activity_calendar > backup_$(date +%Y%m%d).sql
```

### Restore the Database

To restore from a backup:
```bash
sudo mysql -u root activity_calendar < backup_YYYYMMDD.sql
```

### Reset the Database

To completely reset and re-import:
```bash
sudo mysql -u root -e "DROP DATABASE activity_calendar; CREATE DATABASE activity_calendar;"
sudo mysql -u root activity_calendar < /var/www/html/sql/schema.sql
```

## Updating the Application

To pull the latest code from GitHub:
```bash
cd /var/www/html
sudo git pull origin main
sudo systemctl restart apache2
```

## Managing Services

| Command | Description |
|---------|-------------|
| `sudo systemctl start apache2` | Start Apache |
| `sudo systemctl stop apache2` | Stop Apache |
| `sudo systemctl restart apache2` | Restart Apache |
| `sudo systemctl start mariadb` | Start MariaDB |
| `sudo systemctl stop mariadb` | Stop MariaDB |
| `sudo systemctl restart mariadb` | Restart MariaDB |
| `sudo systemctl enable apache2` | Auto-start Apache on boot |
| `sudo systemctl enable mariadb` | Auto-start MariaDB on boot |

## File Permissions

If you encounter permission errors:
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

## Network Configuration

Find the Pi's IP address:
```bash
hostname -I
```

For the demo presentation (link-local networking, WiFi off), connect via USB or Ethernet cable directly to the Pi.

## Adding New Categories

To add a new category directly in the database:
```bash
sudo mysql -u root activity_calendar -e "INSERT INTO categories (name, color, icon) VALUES ('Music', '#E91E63', '🎵');"
```

## Changing the Database Password

```bash
sudo mysql -u root
```
```sql
ALTER USER 'caluser'@'localhost' IDENTIFIED BY 'newpassword';
FLUSH PRIVILEGES;
EXIT;
```

Then update `config.php`:
```bash
sudo nano /var/www/html/php/config.php
```
