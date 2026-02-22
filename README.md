# Smart CRM

Smart CRM is a role-based CRM web application built with Laravel.  
It supports lead management, follow-up tracking, opportunity pipeline updates, bulk CSV imports, and in-app notifications.

## Table of Contents
- Overview
- Tech Stack
- Features
- Screenshots
- Authentication and Demo Credentials
- Installation
- Environment Setup
- Database and Seeding
- Running the Application
- Project Structure
- Roles and Access Rules
- CSV Import Formats
- Useful Commands
- Troubleshooting

## Overview
This CRM provides:
- A dashboard for lead and sales insights.
- Lead lifecycle management with scoring and follow-up planning.
- Follow-up scheduling and status tracking.
- Opportunity pipeline management by stage.
- Notification polling and role-based access control.

## Tech Stack
- Backend: PHP 8+, Laravel 9
- Frontend: Blade templates, TailwindCSS (CDN), jQuery
- Charts and tables: Chart.js, DataTables
- Build tool: Vite
- Database: MySQL/MariaDB (Laravel Eloquent ORM + migrations)
- Auth: Session-based Laravel authentication

## Features
- Role-based login (`admin`, `sales_executive`)
- Lead CRUD with filters and scoring
- CSV import for Leads, Follow-ups, and Opportunities
- Follow-up status updates (`pending`, `completed`, `missed`)
- Opportunity stage updates
- Dashboard KPI cards and charts
- Notification dropdown with polling

## Screenshots
Add screenshots under `docs/screenshots/` using the same file names:

<img width="1360" height="605" alt="image" src="https://github.com/user-attachments/assets/e892e02f-f54f-4de8-b4b6-b8a5955addd6" />
<img width="1361" height="615" alt="image" src="https://github.com/user-attachments/assets/b01f6e80-8d61-44eb-87e5-932151a493d3" />
<img width="1361" height="611" alt="image" src="https://github.com/user-attachments/assets/72e0bd92-6d3a-4344-a044-79970b3c94b9" />
<img width="1363" height="612" alt="image" src="https://github.com/user-attachments/assets/b4d44ea2-31d2-4b59-8019-0d8d9baa7e22" />

If images are not visible yet, place the PNG files in `docs/screenshots/` and refresh your Markdown preview.

## Authentication and Demo Credentials
Seeded users from `database/seeders/DatabaseSeeder.php`:

### Admin
- Email: `admin@crm.local`
- Password: `password`
- Role: `admin`

### Sales Executives
- `alex@crm.local` / `password`
- `maria@crm.local` / `password`
- `ravi@crm.local` / `password`

> Note: Change these credentials before production use.

## Installation
1. Clone the repository.
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install frontend dependencies:
   ```bash
   npm install
   ```
4. Create environment file:
   ```bash
   cp .env.example .env
   ```
5. Generate app key:
   ```bash
   php artisan key:generate
   ```

## Environment Setup
Update `.env` with your database connection:

```env
APP_NAME="Smart CRM"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm
DB_USERNAME=root
DB_PASSWORD=
```

## Database and Seeding
Run migrations and seed demo data:

```bash
php artisan migrate --seed
```

This creates users, sample leads, and related CRM demo records.

## Running the Application
Run backend and frontend in separate terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

Open `http://localhost:8000/login`.

## Project Structure
- `app/Http/Controllers/` - Feature controllers (Auth, Leads, Follow-ups, Opportunities, Dashboard)
- `app/Models/` - Eloquent models
- `app/Services/` - Domain logic (scoring, imports, notifications, opportunity automation)
- `app/Http/Requests/` - Form request validation
- `resources/views/` - Blade UI templates
- `routes/web.php` - Web routes and role-protected endpoints
- `database/migrations/` - Schema definitions
- `database/seeders/DatabaseSeeder.php` - Demo credentials and sample data

## Roles and Access Rules
- `admin`
  - Full CRM access
  - Can delete leads
  - Can broadcast notifications
- `sales_executive`
  - Access to assigned leads/follow-ups/opportunities
  - Can update follow-up and opportunity progress for authorized records

Access control is enforced in routes and controllers.

## CSV Import Formats
### Leads Import
Route: `POST /leads/import`  
Expected: CSV file (see lead import form hint in UI).

### Follow-ups Import
Route: `POST /follow-ups/import`  
Columns:
- `lead_id` (required)
- `follow_up_date` (required, date)
- `status` (optional: `pending`, `completed`, `missed`)
- `notes` (optional)

### Opportunities Import
Route: `POST /opportunities/import`  
Columns:
- `name` (required)
- `lead_id` (optional)
- `assigned_user_id` (optional)
- `estimated_value` (optional)
- `probability` (optional, 0-100)
- `expected_close_date` (optional, date)
- `stage` (optional: `prospecting`, `proposal`, `negotiation`, `closed_won`, `closed_lost`)

## Useful Commands
- Run tests:
  ```bash
  php artisan test
  ```
- Clear caches:
  ```bash
  php artisan optimize:clear
  ```
- Build frontend assets:
  ```bash
  npm run build
  ```

## Troubleshooting
- If styles/scripts are missing, ensure `npm run dev` is running.
- If login fails with seeded credentials, rerun:
  ```bash
  php artisan migrate:fresh --seed
  ```
- If file upload/import fails, verify `upload_max_filesize` and `post_max_size` in PHP settings.

---
For feature changes, update this README with new routes, credentials, or module behavior so docs remain accurate.
