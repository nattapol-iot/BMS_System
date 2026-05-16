# Nexus BMS Platform

A modern Building Management System built with Laravel 11 and Bootstrap 5.

## Features

- Dashboard with real-time energy monitoring and system health
- Multi-building management with floor plans and equipment tracking
- Alarm & event management with severity levels
- Energy consumption monitoring and reporting
- Automated scheduling for HVAC, lighting, and access control
- Report generation (PDF/Excel/CSV)
- User access management with role-based permissions
- Bilingual support (Thai / English)
- Complete audit trail with activity logs

## Requirements

- PHP 8.2+
- MySQL 8.0+ or MariaDB 10.6+
- Composer 2.x
- Node.js 18+ (for asset compilation, optional)

## Installation

1. Clone the repository:
   ```bash
   git clone <repo-url>
   cd nexus-bms
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Set up database in `.env`:
   ```env
   DB_DATABASE=nexus_bms
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. Run migrations and seed data:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. Start the development server:
   ```bash
   php artisan serve
   ```

7. Access the application:
   - URL: http://localhost:8000
   - Email: admin@nexus.com
   - Password: admin1234

## Tech Stack

- **Backend:** PHP 8.2, Laravel 11
- **Frontend:** Bootstrap 5.3, ApexCharts, Font Awesome 6
- **Database:** MySQL / MariaDB
- **Fonts:** Google Fonts (Inter, Prompt)

## Database

21 tables covering: users, roles, buildings, floors, rooms, equipment, alarms, energy meters, schedules, reports, settings, activity logs.

## Default Accounts

| Email | Password | Role |
|-------|----------|------|
| admin@nexus.com | admin1234 | Admin |
| manager@nexus.com | manager1234 | Manager |
| operator@nexus.com | operator1234 | Operator |

## License

MIT
