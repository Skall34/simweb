# SimWeb — Copilot Instructions

SimWeb is a **virtual airline (VA) management system** for Microsoft Flight Simulator pilots. It is a PHP/MySQL web application with no framework — procedural PHP with file includes.

---

## Architecture

```
simweb/
├── index.php / login.php / logout.php / live_flights.php  ← root-level pages
├── pages/          ← pilot-facing pages (require login)
│   └── doc_scripts/← in-app documentation for each cron script
├── admin/          ← admin-only pages
├── api/            ← REST API endpoints (JSON, consumed by SimAddon C# client)
├── scripts/        ← CRON job scripts (salaries, insurance, maintenance, etc.)
│   └── logs/       ← all script log files
├── includes/       ← shared PHP modules (business logic, DB, auth, mail)
├── lang/           ← translation arrays: fr.php, en.php, es.php
├── css/styles.css  ← single global stylesheet
├── assets/         ← images, ACARS zip
└── config.ini      ← runtime config (not in git; see includes/config_exemple.php)
```

### Request lifecycle

Every page follows this pattern:
```php
session_start();
include("includes/header.php");          // sets up HTML, loads config + lang
require_once("includes/db_connect.php"); // creates global $pdo (PDO)
// optionally: require_once("includes/require_login.php");
// optionally: require_once("includes/require_admin.php");
include("includes/menu_logged.php");     // or menu_guest.php
// ... page logic ...
include("includes/footer.php");
```

`includes/header.php` always calls `require_once 'config.php'` and `require_once '../lang.php'`, so those are available everywhere after header is included.

---

## Database

- Connection: PDO via global `$pdo`, provided by `includes/db_connect.php`.
- All table names are **UPPERCASE** (e.g., `PILOTES`, `FLOTTE`, `FLEET_TYPE`, `LIGNES_REGULIERES`, `RESERVATIONS`, `BALANCE_COMMERCIALE`).
- Financial tables: `finances_recettes`, `finances_depenses` (lowercase).
- Always use **prepared statements** with named or positional placeholders.
- Config values (business rules like multipliers, messages) are stored in `VARIABLES_CONFIG (nom, valeur)`.
- Admin status: `PILOTES.admin = 1`. Super admins: `VA_SUPER_ADMIN_CALLSIGNS` constant from config.

---

## Internationalisation (i18n)

The app is **trilingual: FR / EN / ES**. All user-visible strings must use the translation system.

- `t('key')` — use in pages/templates (requires an active session)
- `t_mail('key', [], $lang)` — use in scripts/CRON jobs that run without a session

**When adding any UI text or modifying existing strings:**
1. Add/update the key in **all three** lang files: `lang/fr.php`, `lang/en.php`, `lang/es.php`.
2. For documentation pages (`pages/doc_scripts/`), keys are prefixed `doc_`.
3. Use `lang/check_keys.php` to verify all three files have the same keys.

Placeholders in translation strings use either `:param` or `{param}` syntax. Special auto-replaced values: `{VA_NAME}`, `{VA_CONTACT_EMAIL}`, `{VA_ADMIN_EMAIL}`, `{year}`.

---

## Styling

- **No inline CSS.** All styles go in `css/styles.css`. Never add `style="..."` attributes to HTML unless there is a strong justification.
- When creating or modifying a page, add the necessary CSS classes to `css/styles.css`.

---

## Authentication & Access Control

- `includes/require_login.php` — redirects to `/index.php` if not logged in. Include at top of any pilot-only page.
- `includes/require_admin.php` — checks `PILOTES.admin = 1`; redirects to `/index.php` if not admin. Includes `db_connect.php` automatically.
- Session user data is in `$_SESSION['user']` (structured array with `id`, `callsign`, etc.).

---

## API Endpoints (`api/`)

- All endpoints return **JSON** (`Content-Type: application/json`).
- Consumed by the SimAddon C# client (MSFS addon) and AJAX calls from the frontend.
- Flight import (`api_import_vol_direct.php`) validates via `session_token` against `simaddon_tokens` table using `check_token()` from `includes/tokens.php`.
- Log to `scripts/logs/<script>.log` using `logMsg()` from `includes/log_func.php`.

---

## CRON Scripts (`scripts/`)

Scripts run as scheduled jobs (via server CRON). They are authenticated using `CRON_SECRET_TOKEN` from config. Each script:
- Uses `t_mail()` for any translated strings (no session).
- Writes logs to `scripts/logs/` via `logMsg()`.
- Has a corresponding **in-app documentation page** in `pages/doc_scripts/doc_<scriptname>.php`.

**After modifying a cron script's logic, update the corresponding `pages/doc_scripts/` page and any relevant lang keys (`doc_*`).**

---

## Key Business Logic Modules

| File | Purpose |
|------|---------|
| `includes/fonctions_importer_vol.php` | Flight import: duplicate detection, GPS trace, fleet update |
| `includes/fonctions_financieres.php` | Insert revenues/expenses, update `BALANCE_COMMERCIALE` |
| `includes/calcul_cout.php` | Flight cost calculation (distance, payload, fuel, mission bonus) |
| `includes/mail_utils.php` | PHPMailer wrapper with retry logic |
| `includes/log_func.php` | `logMsg($message, $logFile)` utility |
| `includes/tokens.php` | SimAddon token validation |

---

## Configuration

Runtime config is in `config.ini` (root, not committed). Reference template: `includes/config_exemple.php` and `includes/db_connect_exemple.php`. Parsed in `includes/config.php` into PHP `define()` constants (e.g., `VA_NAME`, `DB_HOST`, `SMTP_HOST`, `CRON_SECRET_TOKEN`).

---

## Documentation Workflow

After any code or logic change:
1. Update the in-app doc page in `pages/doc_scripts/` if the change affects a documented script.
2. Update lang keys (`doc_*` prefix) in all three lang files.
3. Keep `Documentation/COPILOT_MEMORY.md` in sync if permanent rules are added or changed.
