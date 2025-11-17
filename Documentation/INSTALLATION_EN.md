# 📖 Installation Guide - Virtual Airline Management System

**Version:** 2.0  
**Date:** November 2025  
**Supported Languages:** French, English, Spanish

---

## 📋 Table of Contents

1. [Requirements](#requirements)
2. [Web Server Installation](#web-server-installation)
3. [Database Configuration](#database-configuration)
4. [Application Configuration](#application-configuration)
5. [Email Configuration](#email-configuration)
6. [Scheduled Tasks Configuration (optional)](#scheduled-tasks-configuration)
7. [First Launch](#first-launch)
8. [Administrator Account Creation](#administrator-account-creation)
9. [Troubleshooting](#troubleshooting)

---

## 🔧 Requirements

### Web Server
- **PHP 7.4** or higher (recommended: PHP 8.1+)
- **MySQL 5.7** or higher (or MariaDB 10.3+)
- **Apache** or **Nginx** with mod_rewrite enabled
- **HTTPS** (SSL certificate recommended for production)

### Required PHP Extensions
- `pdo_mysql`
- `mbstring`
- `json`
- `curl`
- `openssl`
- `session`

### Disk Space
- Minimum: **500 MB**
- Recommended: **2 GB** (for logs and data)

---

## 🔍 Environment Check (Recommended)

Before starting installation, we recommend using the verification script:

1. **Upload** the file `Documentation/check_installation.php` to your server root
2. **Access** `http://your-domain.com/check_installation.php`
3. **Verify** all requirements are OK (PHP, extensions, permissions)
4. ⚠️ **Delete** this file after verification

✅ This script will tell you exactly what's missing before proceeding.

---

## 🌐 Web Server Installation

### Option 1: Shared Hosting Installation

1. **Download the system ZIP file**
2. **Extract** the contents on your computer
3. **Upload all files** via FTP to your hosting root folder (usually `/public_html` or `/www`)
4. **Check permissions**:
   - `scripts/logs/` folders: **755** (read/write)
   - All other files: **644**

### Option 2: Dedicated Server/VPS Installation

#### On Ubuntu/Debian:
```bash
# Update the system
sudo apt update && sudo apt upgrade -y

# Install Apache, PHP and MySQL
sudo apt install apache2 php php-mysql php-mbstring php-json php-curl mysql-server -y

# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Create the application folder
sudo mkdir -p /var/www/skywings
sudo chown -R www-data:www-data /var/www/skywings

# Copy application files
# (extract ZIP to /var/www/skywings)
```

#### On Windows (XAMPP/WAMP):
1. Install **XAMPP** or **WAMP**
2. Extract ZIP to `C:\xampp\htdocs\yourva\` (or equivalent)
3. Start Apache and MySQL from control panel

---

## 💾 Database Configuration

### Import SQL Scripts

**Two SQL files must be imported in order:**

#### Via PhpMyAdmin:
1. Log in to **PhpMyAdmin**
2. Click the **"Import"** tab
3. **First import**: Select `sql_database/01_Main_Database.sql`
   - ✅ This file automatically creates the `VA_Database` database and all tables
4. Click **"Execute"** and wait (1-2 minutes)
5. **Second import**: Select `sql_database/02_Airports_data.sql`
   - ✅ This file adds airport data
6. Click **"Execute"** and wait (may take a few minutes)

#### Via Command Line:
```bash
mysql -u root -p < sql_database/01_Main_Database.sql
mysql -u root -p VA_Database < sql_database/02_Airports_data.sql
```

✅ **The database is now created with:**
- All necessary tables
- Airport data
- A default administrator account: **ADM0001** (password: `admin123`)

---

## ⚙️ Application Configuration

### Step 1: Database Connection Configuration

1. **Locate the file** `includes/db_connect_exemple.php`
2. **Rename it** to `includes/db_connect.php`
3. **Edit the file** with your credentials:

```php
<?php
$host = 'localhost';          // MySQL server address
$db   = 'yourva';           // Your database name
$user = 'yourva_user';      // MySQL user
$pass = 'YourSecurePassword'; // MySQL password
$charset = 'utf8mb4';

// Don't modify the following lines
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit("Database connection error: " . $e->getMessage());
}
```

### Step 2: Connection Verification

Access your site: `http://your-domain.com/`

✅ **If working**: You'll see the homepage  
❌ **If error**: Check your database credentials

---

## 📧 Email Configuration

The system uses **PHPMailer** to send emails (notifications, summaries, etc.)

### Step 1: SMTP Server Configuration

Edit the file `includes/mail_utils.php`:

```php
// Line 18: Administrator email address
define('ADMIN_EMAIL', 'admin@your-domain.com');

// Lines 24-28: SMTP Configuration
$mail->Host = 'smtp.your-host.com';           // SMTP server
$mail->Username = 'admin@your-domain.com';    // SMTP email
$mail->Password = 'YourSMTPPassword';         // SMTP password
$mail->SMTPSecure = 'tls';                    // 'tls' or 'ssl'
$mail->Port = 587;                            // 587 (TLS) or 465 (SSL)
```

### Popular SMTP Configuration Examples:

#### Gmail:
```php
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';  // See: https://support.google.com/accounts/answer/185833
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```

#### OVH:
```php
$mail->Host = 'ssl0.ovh.net';
$mail->Username = 'admin@your-domain.com';
$mail->Password = 'your-password';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```

#### Office 365 / Outlook:
```php
$mail->Host = 'smtp.office365.com';
$mail->Username = 'admin@your-domain.com';
$mail->Password = 'your-password';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```

### Step 2: Email Testing

Emails are automatically sent for:
- New pilot registration
- Password reset
- Grade promotions
- Monthly script summaries

---

## ⏰ Scheduled Tasks Configuration

Automatic scripts maintain the system (insurance, salaries, promotions, etc.)

### Available scripts in `scripts/` folder:

| Script | Frequency | Description |
|--------|-----------|-------------|
| `assurance_mensuelle.php` | 1x/month (1st at 3am) | Charges insurance on all aircraft |
| `credit_mensualite.php` | 1x/month (1st at 2am) | Charges installments for financed aircraft |
| `paiement_salaires_pilotes.php` | 1x/month (1st at 1am) | Pays pilots based on flight hours |
| `promotion_grades_pilotes.php` | 1x/month (1st at 11pm) | Promotes pilots based on hours |
| `update_fret.php` | 1x/week (Friday 4am) | Adds random freight to airports |
| `expire_reservations.php` | 1x/day (2am) | Cancels expired reservations |
| `maintenance.php` | 1x/month (1st at 4am) | Applies wear to aircraft |
| `rotate_logs.php` | 1x/month (1st at 5am) | Archives old logs |

### Linux Configuration (crontab):

```bash
# Edit crontab
sudo crontab -e

# Add these lines (adjust path /var/www/yourva):
0 1 1 * * /usr/bin/php /var/www/yourva/scripts/paiement_salaires_pilotes.php
0 2 1 * * /usr/bin/php /var/www/yourva/scripts/credit_mensualite.php
0 3 1 * * /usr/bin/php /var/www/yourva/scripts/assurance_mensuelle.php
0 4 1 * * /usr/bin/php /var/www/yourva/scripts/maintenance.php
0 5 1 * * /usr/bin/php /var/www/yourva/scripts/rotate_logs.php
0 23 1 * * /usr/bin/php /var/www/yourva/scripts/promotion_grades_pilotes.php
0 4 * * 5 /usr/bin/php /var/www/yourva/scripts/update_fret.php
0 2 * * * /usr/bin/php /var/www/yourva/scripts/expire_reservations.php
```

### Windows Configuration (Task Scheduler):

1. Open **Windows Task Scheduler**
2. Create a new task:
   - **Trigger**: Daily / Monthly depending on script
   - **Action**: Start a program
   - **Program**: `C:\xampp\php\php.exe`
   - **Arguments**: `C:\xampp\htdocs\yourva\scripts\script_name.php`

### Shared Hosting Configuration (cPanel):

1. Log in to **cPanel**
2. Find **"Cron Jobs"**
3. Add tasks with syntax:
   ```
   0 1 1 * * /usr/bin/php /home/your-user/public_html/scripts/paiement_salaires_pilotes.php
   ```

---

## 🚀 First Launch

### Step 1: Access the Site

Open your browser and go to:
```
http://your-domain.com/
```

You should see the homepage with:
- Your company logo
- Live flights (none yet)
- Login form

### Step 2: Login with Default Administrator Account

1. Log in with the following credentials:
   - **Callsign**: `ADM0001`
   - **Password**: `admin123`

2. ✅ You should now see the **"Admin"** menu at the top

### Step 3: Create Your Own Administrator Account

⚠️ **IMPORTANT for security**: The `ADM0001` account must be deleted after this step.

1. Click **"Logout"**
2. Click **"Register"**
3. Fill out the form with **your information**:
   - **Callsign**: Your callsign (e.g., ABC0001)
   - **Last Name** and **First Name**: Your information
   - **Email**: Your real email
   - **Password**: A secure password
4. Submit registration

### Step 4: Promote Your Account to Administrator

Log back in with the **ADM0001** account, then:

1. Go to **Admin** → **Pilot Management**
2. Find your new account in the list
3. Check the **"Admin"** checkbox on your row
4. Save

### Step 5: Delete the Default Account

⚠️ **Critical for security**:

1. Log out of `ADM0001`
2. Log back in with **your own account**
3. Go to **Admin** → **Pilot Management**
4. **Delete** the `ADM0001` account

✅ Your installation is now secure!

---

## 🎨 Customization (optional)

### Change Company Name

Edit the file `includes/header.php`:
```php
<div class="nom-compagnie">Your VA</div>  <!-- Line 25 approximately -->
```

### Change Logo

Replace the file `assets/images/logo.png` with your own logo (PNG, 150x150px recommended)

### Modify Colors

Edit the file `css/styles.css`:
```css
/* Primary color (dark blue) */
.btn {
  background-color: #004080;  /* Line 235 - Change this value */
}
```

---

## 🔍 Troubleshooting

### 💡 Use the Diagnostic Script

If you encounter problems, use the verification script:
```
http://your-domain.com/check_installation.php
```
It will tell you exactly what's wrong (PHP extensions, permissions, database connection, etc.).

⚠️ Don't forget to delete it after use.

---

### Problem: Blank Page

**Cause**: PHP error not displayed

**Solution**:
1. Enable error display in `includes/db_connect.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
2. Check Apache logs: `/var/log/apache2/error.log`

### Problem: "Database Connection Error"

**Possible Causes**:
- Incorrect credentials in `db_connect.php`
- MySQL not started
- Firewall blocking port 3306

**Solution**:
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u yourva_user -p yourva
```

### Problem: Emails Not Sending

**Possible Causes**:
- Incorrect SMTP configuration
- Firewall blocking SMTP ports

**Solution**:
1. Check `includes/mail_utils.php`
2. Test with simple script:
```bash
php -r "echo mail('test@example.com', 'Test', 'Message body') ? 'OK' : 'ERROR';"
```

### Problem: Error 500 After Modification

**Cause**: PHP syntax error

**Solution**:
1. Check Apache logs
2. Revert last change
3. Check syntax with:
```bash
php -l your-file.php
```

### Problem: Automatic Scripts Not Working

**Possible Causes**:
- Cron not configured
- Insufficient permissions on `scripts/logs/` folder

**Solution**:
```bash
# Set correct permissions
sudo chmod 755 scripts/logs/
sudo chown -R www-data:www-data scripts/logs/

# Test script manually
php scripts/assurance_mensuelle.php
```

### Problem: "RewriteEngine not available"

**Cause**: mod_rewrite not enabled

**Solution**:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 📚 Additional Resources

### Technical Documentation
Check the documentation pages in the **Admin → Documentation** menu to understand the internal workings of scripts.

### Support
- **GitHub**: [Report a bug](https://github.com/Skall34/simweb/issues)
- **Discord**: [Join the community](https://discord.gg/K52UfAnSdk)

### SimAddon (Flight Simulator Client)
For your pilots to automatically record their flights from Microsoft Flight Simulator, they must install **SimAddon**.

Documentation available in `assets/acars/DocumentationUtilisateurSimAddon.pdf`

---

## ✅ Final Checklist

Before going to production, verify that:

- [ ] Database is imported and accessible
- [ ] `db_connect.php` file is configured with correct credentials
- [ ] Emails are configured and tested
- [ ] Administrator account is created and functional
- [ ] Cron tasks are configured (optional but recommended)
- [ ] HTTPS is enabled (SSL certificate)
- [ ] Folder permissions are correct (755 for logs/)
- [ ] Site is accessible from outside
- [ ] All pages load without errors
- [ ] Language switching works

---

## 🎉 Congratulations!

Your virtual airline is now operational!

You can:
- Create custom missions
- Manage your aircraft fleet
- Track pilot performance
- View statistics and finances

**Happy flying! ✈️**

---

*Guide created with ❤️ by the flight simulation community*  
*Version 2.0 - November 2025*
