# Briefly

Daily team progress tracking application built with **Laravel** and **Bootstrap 5**.

## Features

- Authentication with two roles: **Member** and **Team Lead**
- Daily Updates (Done / In Progress / Blocker + status 🟢🟠🔴)
- Team-scoped data isolation
- Team Lead dashboard, team updates view, daily brief (draft/publish)
- Published brief history with date and keyword search

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve
```

Visit `http://localhost:8000`

## Demo accounts

Password for all accounts: `password`

| Team | Role | Email |
|------|------|-------|
| Web Team | Team Lead | lead@web.test |
| Web Team | Member | alice@web.test |
| Mobile Team | Team Lead | lead@mobile.test |
| Mobile Team | Member | alice@mobile.test |

## Tests

```bash
php artisan test
```

## Documentation

See [`docs/project_brief.pdf`](docs/project_brief.pdf) for the full project specification.
