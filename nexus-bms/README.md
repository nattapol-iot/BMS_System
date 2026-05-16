# Nexus BMS Platform

A modern Building Management System (BMS) for monitoring buildings, equipment,
alarms, energy usage, schedules, reports, users, and system settings.

## Current Local Status

This project is currently configured for local development.

- Application URL: `http://localhost:8000`
- Login URL: `http://localhost:8000/login`
- Environment: `APP_ENV=local`
- Debug mode: `APP_DEBUG=true`
- Database connection: MySQL on `127.0.0.1:3306`
- Database name: `nexus_bms`
- Default local database user: `root`

The root path `/` redirects to `/login`.

## Features

- Dashboard with energy monitoring and system health widgets
- Multi-building management with floors, rooms, and equipment tracking
- Equipment CRUD with API helper search endpoint
- Alarm and event management with acknowledge, resolve, silence, and assign actions
- Energy consumption monitoring and reporting views
- Automated scheduling with calendar, device settings, toggle, create, edit, and delete flows
- Report generation endpoints
- User access management with role-based permissions
- Bilingual support for Thai and English
- Settings and manual backup trigger
- Activity logs and audit trail

## Requirements

- PHP 8.2+
- MySQL 8.0+ or MariaDB 10.6+
- Composer 2.x
- Node.js 18+ and npm, required when compiling or watching frontend assets

## Installation

1. Clone the repository:
   ```bash
   git clone <repo-url>
   cd nexus-bms
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Configure the environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Update the database settings in `.env` if needed:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nexus_bms
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Run migrations and seed demo data:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. Install and build frontend assets when needed:
   ```bash
   npm install
   npm run build
   ```

## Running Locally

Start only the Laravel development server:

```bash
php artisan serve
```

Then open:

```text
http://localhost:8000
```

For a fuller development session with Laravel server, queue listener, logs, and
Vite watcher, use:

```bash
composer run dev
```

## Default Accounts

| Email | Password | Role |
|-------|----------|------|
| admin@nexus.com | admin1234 | Admin |
| manager@nexus.com | manager1234 | Manager |
| operator@nexus.com | operator1234 | Operator |

## Main Routes

- `/login` - Sign in
- `/dashboard` - Main dashboard
- `/buildings` - Buildings
- `/floors` - Floors
- `/equipment` - Equipment
- `/alarms` - Alarms
- `/energy` - Energy monitoring
- `/schedules` - Schedules
- `/reports` - Reports
- `/users` - User management
- `/settings` - System settings
- `/logs` - Activity logs

## Tech Stack

- **Backend:** PHP 8.2+, Laravel 12
- **Frontend:** Bootstrap 5.3, ApexCharts, Font Awesome 6, Vite
- **Build tooling:** Node.js, npm, Vite 7
- **Database:** MySQL / MariaDB
- **Fonts:** Google Fonts (Inter, Prompt)

## Database

The project includes 23 migrations covering Laravel system tables and BMS domain
tables:

- users, roles, permissions, role permissions
- buildings, floors, rooms
- equipment categories, equipment, equipment status logs
- alarms, alarm events
- energy meters, energy logs
- schedules, schedule devices, schedule runs
- reports, system settings, activity logs
- cache and jobs tables

Seeders are available for roles, users, buildings, equipment categories,
equipment, alarms, energy data, schedules, and system settings.

## Local Notes

- The included `.env.example` is already set up for local MySQL development.
- If PHP prints warnings about missing `sqlsrv` extensions, they are unrelated to
  the default MySQL setup unless SQL Server support is required.
- Use `php artisan migrate:status` to verify migration state.
- Use `php artisan route:list` to inspect available routes.

## License

MIT
