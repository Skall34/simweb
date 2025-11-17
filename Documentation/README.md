# ✈️ Virtual Airline Management System

![Version](https://img.shields.io/badge/version-2.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-MIT-green)
![Languages](https://img.shields.io/badge/languages-FR%20%7C%20EN%20%7C%20ES-orange)

A complete web-based virtual airline management system for Microsoft Flight Simulator communities. It includes flight tracking, fleet management, pilot statistics, missions, and full integration with the SimAddon client for automatic flight recording.

---

## 🌟 Features

### For Pilots
- ✈️ **Automatic Flight Recording** via SimAddon (MSFS addon)
- 📊 **Personal Statistics** (hours, grades, salaries)
- 🗺️ **Custom Missions** (special flights, freight, humanitarian)
- 🛩️ **Aircraft Reservation** system
- 📈 **Grade Progression** based on flight hours
- 💰 **Virtual Economy** (salaries, aircraft purchases)
- 🌍 **Multi-language** (French, English, Spanish)

### For Administrators
- 🏢 **Fleet Management** (purchase, sell, maintenance)
- 👥 **Pilot Management** (grades, activation, statistics)
- 🎯 **Mission Creation** (routes, special events)
- 📧 **Email Notifications** (promotions, reports)
- 💵 **Financial Dashboard** (income, expenses, balance)
- ⚙️ **Automated Scripts** (insurance, salaries, maintenance)
- 🔧 **Configuration Panel** (variables, airports, aircraft types)

---

## 📋 Requirements

- **PHP 7.4+** (recommended 8.1+)
- **MySQL 5.7+** or MariaDB 10.3+
- **Apache** or Nginx with mod_rewrite
- **PHPMailer** (included)
- **SSL Certificate** (recommended for production)

---

## 🚀 Quick Start

### 1. Download & Extract
Download the latest release and extract to your web server directory.

### 2. Check Environment (Recommended)
Upload and run `Documentation/check_installation.php`:
```
http://your-domain.com/check_installation.php
```
✅ Verify all requirements are met, then delete the file.

### 2. Database Setup
```bash
# Import SQL scripts in order
mysql -u root -p < sql_database/01_Main_Database.sql
mysql -u root -p VA_Database < sql_database/02_Airports_data.sql
```
✅ Default admin account created: `ADM0001` / `admin123`

### 3. Configure Database Connection
```bash
cp includes/db_connect_exemple.php includes/db_connect.php
# Edit includes/db_connect.php with your credentials
```

### 4. First Login
1. Open `http://your-domain.com/`
2. Login with: `ADM0001` / `admin123`
3. Create your own admin account
4. ⚠️ Delete `ADM0001` for security

📖 **For detailed installation instructions, see:**
- 🇫🇷 [INSTALLATION.md](INSTALLATION.md) (Français)
- 🇬🇧 [INSTALLATION_EN.md](INSTALLATION_EN.md) (English)

---

## 📁 Project Structure

```
yourva/
├── admin/              # Administration pages
├── api/                # API endpoints for SimAddon
├── assets/             # Images, ACARS documentation
├── css/                # Stylesheets
├── includes/           # PHP utilities, database, authentication
├── lang/               # Translations (fr.php, en.php, es.php)
├── pages/              # Public pages (flights, stats, missions...)
├── scripts/            # Automated maintenance scripts
├── sql_database/       # Database creation & structure
└── tools/              # Development utilities
```

---

## 🔄 Automated Scripts

SkyWings includes automated scripts for realistic airline operations:

| Script | Frequency | Function |
|--------|-----------|----------|
| `assurance_mensuelle.php` | Monthly | Charges insurance on all aircraft |
| `credit_mensualite.php` | Monthly | Processes loan payments |
| `paiement_salaires_pilotes.php` | Monthly | Pays pilot salaries |
| `promotion_grades_pilotes.php` | Monthly | Promotes pilots based on hours |
| `maintenance.php` | Monthly | Applies wear to aircraft |
| `update_fret.php` | Weekly | Adds freight to airports |
| `expire_reservations.php` | Daily | Cancels expired reservations |

Configure with cron (Linux) or Task Scheduler (Windows) - see installation guide.

---

## 🌐 Multi-Language Support

Full interface translation in 3 languages:
- 🇫🇷 **French** (Français)
- 🇬🇧 **English**
- 🇪🇸 **Spanish** (Español)

**944 translation keys** covering all pages and features.

---

## 🔌 SimAddon Integration

**SimAddon** is the companion MSFS addon that automatically records flights:
- Real-time flight tracking
- Automatic data upload (departure, arrival, duration, fuel)
- GPS trace recording
- Token-based authentication

Documentation: `assets/acars/DocumentationUtilisateurSimAddon.pdf`

---

## 🛠️ Configuration

### Email Setup
Edit `includes/mail_utils.php`:
```php
define('ADMIN_EMAIL', 'admin@your-domain.com');
$mail->Host = 'smtp.your-host.com';
$mail->Username = 'admin@your-domain.com';
$mail->Password = 'your-password';
```

### Customization
- **Company name**: `includes/header.php`
- **Logo**: `assets/images/logo.png`
- **Colors**: `css/styles.css`

---

## 📚 Documentation

- **User Guide**: Available in-app under "Documentation" menu
- **Admin Guide**: Access via Admin panel
- **API Reference**: See `api/` folder for SimAddon integration
- **Script Documentation**: Detailed docs in `pages/doc_scripts/`

---

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push and create a Pull Request

---

## 🐛 Support

- **Issues**: [GitHub Issues](https://github.com/Skall34/simweb/issues)
- **Discord**: [Join our community](https://discord.gg/K52UfAnSdk)
- **Email**: Contact via the in-app contact form

---

## 📜 License

This project is licensed under the MIT License - see [LICENSE.txt](LICENSE.txt) for details.

---

## 🙏 Credits

Created with ❤️ by the flight simulation community for virtual airline enthusiasts worldwide.

**Special thanks to:**
- All beta testers and contributors
- The MSFS community
- PHPMailer developers

---

## 🎯 Roadmap

- [ ] Mobile-responsive design improvements
- [ ] REST API for third-party integrations
- [ ] Advanced statistics and analytics
- [ ] Multiplayer events system
- [ ] Real-time flight map with WebSocket
- [ ] Integration with real-world weather data

---

**Happy flying! ✈️**

*For detailed installation instructions, refer to INSTALLATION.md (French) or INSTALLATION_EN.md (English)*
