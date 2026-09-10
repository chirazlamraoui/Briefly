# Briefly

Daily team progress tracking application built with **Laravel** and **Bootstrap 5**.

## Features

- Authentication with two roles: **Member** and **Team Lead**
- Daily Updates (Done / In Progress / Blocker + status 🟢🟠🔴)
- Team-scoped data isolation
- Team Lead dashboard, team updates view, daily brief (draft/publish)
- Published brief history with date and keyword search
- Shared team blockers with recurrence chart on the lead dashboard
- Member update history, daily submission reminders, and deadline (timezone-aware)
- Brief export (PDF download + copy to clipboard)
- Dark mode and FR/EN language switcher

## Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 13, PHP 8.3+ |
| Database | **MySQL** (XAMPP on macOS) |
| Frontend | Bootstrap 5, Blade |
| Tests | PHPUnit (SQLite in-memory, isolated from XAMPP) |

## Setup with XAMPP

### 1. Start XAMPP

Open **XAMPP** and start:

- **Apache** (phpMyAdmin on macOS uses port **8080**)
- **MySQL** (port **3306**)

### 2. Create the database

**Option A — Terminal**

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root < database/sql/xampp-setup.sql
```

**Option B — phpMyAdmin**

Open [http://localhost:8080/phpmyadmin](http://localhost:8080/phpmyadmin)

- User: `root`
- Password: *(empty — XAMPP default)*

Run the SQL from `database/sql/xampp-setup.sql` or create database `briefly` with collation `utf8mb4_unicode_ci`.

### 3. Configure the application

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Visit **http://localhost:8000**

### Environment (`.env`)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=briefly
DB_USERNAME=root
DB_PASSWORD=
```

If your XAMPP `root` user has a password, set `DB_PASSWORD` accordingly.

### Timezone and daily update deadline

```env
APP_TIMEZONE=Europe/Paris
DAILY_UPDATE_DEADLINE=17:00
```

Members see a dashboard reminder until they submit their daily update. After the deadline, the reminder switches to a “missing update” alert.

## Local development accounts

> **Development only.** These accounts are created by `php artisan migrate:fresh --seed` on your local machine. They are not shown on the login page and must not be used on a public or production deployment — run `php artisan migrate --force` without `--seed` in production and create real user accounts instead.

Password for all seeded users: **`password`**

| Team | Role | Email |
|------|------|-------|
| Web Team | Team Lead | `lead@web.test` |
| Web Team | Member | `alice@web.test` |
| Web Team | Member | `bob@web.test` |
| Web Team | Member | `carol@web.test` |
| Web Team | Member | `diana@web.test` |
| Web Team | Member | `ethan@web.test` |
| Web Team | Member | `fatima@web.test` |
| Mobile Team | Team Lead | `lead@mobile.test` |
| Mobile Team | Member | `alice@mobile.test` |
| Mobile Team | Member | `bob@mobile.test` |
| Mobile Team | Member | `carol@mobile.test` |
| Mobile Team | Member | `diana@mobile.test` |
| Mobile Team | Member | `ethan@mobile.test` |
| Mobile Team | Member | `fatima@mobile.test` |

## Browse the database

| Tool | URL / command |
|------|----------------|
| phpMyAdmin | http://localhost:8080/phpmyadmin → database `briefly` |
| MySQL CLI | `/Applications/XAMPP/xamppfiles/bin/mysql -u root briefly` |

Example queries:

```sql
SHOW TABLES;
SELECT name, email, role FROM users;
SELECT label FROM blockers;
```

## Tests

Tests use an in-memory SQLite database so XAMPP does not need to be running:

```bash
php artisan test
```

## Reset demo data

```bash
php artisan migrate:fresh --seed
```

## Documentation

See [`docs/project_brief.pdf`](docs/project_brief.pdf) for the full project specification.
