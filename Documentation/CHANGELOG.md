# 📝 Changelog - SkyWings Virtual Airline

All notable changes to this project will be documented in this file.

---

## [2.0.0] - 2025-11-15

### 🌍 Multi-Language Support
- ✅ Complete translation system with 944 keys
- ✅ French (Français) - Full support
- ✅ English - Full support
- ✅ Spanish (Español) - Full support
- ✅ Dynamic language switcher in header
- ✅ Session-based language persistence
- ✅ All pages translated (frontend, admin, documentation)

### 🎨 Design & UI Improvements
- ✅ Unified dark blue button style (#004080)
- ✅ Removed deprecated `.btn-bleu` class
- ✅ CSS consolidation (removed duplicates)
- ✅ Improved responsive design
- ✅ Consistent spacing and margins
- ✅ Better form layouts

### 📧 Email System
- ✅ Centralized email configuration (`mail_utils.php`)
- ✅ PHPMailer integration
- ✅ SMTP configuration support
- ✅ Email notifications for:
  - New pilot registrations
  - Password resets
  - Grade promotions
  - Monthly script summaries

### 🔐 Authentication & Security
- ✅ SimAddon token authentication
- ✅ Session tokens table for external auth
- ✅ Password hashing with bcrypt
- ✅ Admin role-based access control
- ✅ HTTPS redirect via .htaccess

### 📊 New Features
- ✅ Live flights map with Leaflet.js
- ✅ Real-time flight tracking
- ✅ GPS trace visualization
- ✅ Aircraft reservation system with expiration
- ✅ Scheduled lines (lignes régulières)
- ✅ Financial dashboard with balance tracking
- ✅ Mission system with special flights

### 🛠️ Automated Scripts
- ✅ Monthly insurance charges (`assurance_mensuelle.php`)
- ✅ Monthly loan payments (`credit_mensualite.php`)
- ✅ Monthly pilot salaries (`paiement_salaires_pilotes.php`)
- ✅ Monthly grade promotions (`promotion_grades_pilotes.php`)
- ✅ Monthly aircraft maintenance (`maintenance.php`)
- ✅ Weekly freight updates (`update_fret.php`)
- ✅ Daily reservation expiration (`expire_reservations.php`)
- ✅ Monthly log rotation (`rotate_logs.php`)

### 📚 Documentation
- ✅ Complete installation guide (FR & EN)
- ✅ Quick start guide
- ✅ In-app technical documentation for all scripts
- ✅ API documentation for SimAddon integration
- ✅ User manual for SimAddon client

### 🐛 Bug Fixes
- 🔧 Fixed button styling inconsistencies
- 🔧 Fixed CSS centering issues
- 🔧 Corrected select placeholder translations
- 🔧 Fixed inline styles cleanup
- 🔧 Resolved CSS class duplicates

### 🔄 Database Updates
- ✅ Added `simaddon_tokens` table for auth
- ✅ Added `RESERVATIONS` table for line booking
- ✅ Added `VARIABLES_CONFIG` for dynamic settings
- ✅ Optimized indexes for performance

### 📖 Translation Keys Added
- Login page: 5 keys
- Live flights: 5 keys
- Documentation links: 2 keys
- Fleet management: 31 keys
- Form placeholders: 1 key
- Total: **944 keys per language**

---

## [1.0.0] - 2024-XX-XX

### Initial Release
- ✅ Basic flight logging
- ✅ Fleet management
- ✅ Pilot statistics
- ✅ Admin panel
- ✅ French language only
- ✅ SimAddon integration (basic)

---

## Upcoming Features (Roadmap)

### Version 2.1 (Planned)
- [ ] Mobile-responsive improvements
- [ ] Real-time WebSocket for live flights
- [ ] Advanced analytics dashboard
- [ ] Export functionality (CSV, PDF)
- [ ] Pilot achievements system

### Version 2.2 (Planned)
- [ ] REST API for third-party apps
- [ ] Mobile app companion
- [ ] Real-world weather integration
- [ ] Flight planning tools
- [ ] Community events system

### Version 3.0 (Long-term)
- [ ] Multi-airline support
- [ ] Marketplace for aircraft trading
- [ ] Social features (friend list, messages)
- [ ] Advanced mission editor
- [ ] Integration with other simulators (X-Plane, P3D)

---

## Migration Notes

### From 1.x to 2.0
1. **Database**: Run migration scripts in `sql_database/`
2. **Config**: Update `db_connect.php` and `mail_utils.php`
3. **Languages**: Default language is French, users can switch
4. **Buttons**: Old `.btn-bleu` replaced with `.btn` (CSS update)

---

## Contributors

Thanks to all contributors who made version 2.0 possible!

- **Lead Developer**: Skall34
- **AI Assistant**: Claude (Anthropic)
- **Beta Testers**: SkyWings community

---

**For installation help, see [INSTALLATION.md](INSTALLATION.md)**

**For quick setup, see [QUICKSTART.md](QUICKSTART.md)**
