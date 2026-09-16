# Student Information Management REST API

A backend-only RESTful API for managing student records, academic programs, courses, terms, course offerings, enrollments, and grades — built as the AI-Assisted REST API Development laboratory activity. No graphical frontend is included by design; the API is meant to be consumed by Postman, Swagger, curl, or a future client application.

## Technology Stack

- **Framework:** Laravel 11 (PHP 8.2+)
- **Database:** PostgreSQL
- **Authentication:** Laravel Sanctum (token-based API auth)
- **Authorization:** Laravel Policies (role-based + object-level)
- **API Documentation:** OpenAPI/Swagger via `darkaonline/l5-swagger`
- **Query Features:** Custom search/filter/sort/pagination on all collection endpoints

## Prerequisites

- PHP 8.2 or higher
- Composer
- PostgreSQL 13+ (or adjust `.env` for MySQL/SQLite — see Database Setup)
- Git

## Installation

1. Clone the repository:
   ```bash
   git clone <your-repo-url>
   cd student-information-api
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate the application key:
   ```bash
   php artisan key:generate
   ```

## Environment Configuration

Edit `.env` and set the following (adjust to your actual database credentials):

```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=student_api
DB_USERNAME=postgres
DB_PASSWORD=your_password_here
```

`.env` is excluded from version control (see `.gitignore`). A safe `.env.example` with no real credentials is included in the repository for reference.

## Database Setup

1. Create the database (PostgreSQL example):
   ```bash
   psql -U postgres -c "CREATE DATABASE student_api;"
   ```

2. Run migrations to create all tables:
   ```bash
   php artisan migrate
   ```

   To reset and rebuild the database from scratch at any time:
   ```bash
   php artisan migrate:fresh
   ```

## Seeding Test Data

> **Note:** Seeders/factories are planned but not yet implemented as of this version of the README. Once added, this section will document `php artisan db:seed` and the specific record counts generated (per the lab's minimum test data requirements: 5 users, 3 programs, 100 students, 20 courses, 2 academic terms, 20 course offerings, 200 enrollments, 100 grades). Until then, test data can be created manually via `php artisan tinker` or through the API endpoints directly (see Development Test Accounts below for role setup).

## Running the API

Start the local development server:

```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`. All endpoints are versioned under `/api/v1/`.

## Authentication

This API uses **Laravel Sanctum** for token-based authentication (no sessions or cookies — appropriate for a backend-only API with no browser frontend).

**Login:**
```
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@test.com",
  "password": "password"
}
```

Response includes a `token` field. Include it on all subsequent requests:
```
Authorization: Bearer <token>
```

**Get current user:**
```
GET /api/v1/auth/me
```

**Logout (invalidates the current token):**
```
POST /api/v1/auth/logout
```

### Development Test Accounts

The following roles exist in the system. Create test accounts locally via `php artisan tinker`, for example:

```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@test.com',
    'password' => 'password',
    'role' => 'administrator',
]);
```

| Role | Example email | Notes |
|---|---|---|
| Administrator | admin@test.com | Full system access |
| Registrar/Staff | registrar@test.com | Manages students, academic records, programs, courses, enrollments |
| Instructor | instructor@test.com | Manages grades only for their own course offerings |
| Student | student@test.com | Views own profile, enrollments, and grades only |

Passwords for local test accounts are set at creation time and are not stored in this document. **Do not commit real credentials to the repository.**

## API Documentation

Interactive Swagger/OpenAPI documentation is available once the server is running:

```
http://127.0.0.1:8000/api/documentation
```

This documents every endpoint's path, method, required authentication, request/query parameters, request/response schemas, and example payloads, including validation and error response examples.

## API Client Collection

A Postman collection covering all endpoint groups (Authentication, Programs, Courses, Academic Terms, Students, Course Offerings, Enrollments, Grades, Academic Records) — including both successful and intentionally failing requests — is provided at:

```
/postman/student-information-api.postman_collection.json
```

Import this file into Postman, Insomnia, or Bruno to explore and test the API interactively.

## Running Tests

Automated tests cover authentication, CRUD operations, validation, authorization (role-based and object-level), enrollment duplicate prevention, and grade submission.

```bash
php artisan test
```

To run a specific test file:
```bash
php artisan test --filter=StudentTest
```

## Project Structure

```
app/
  Http/
    Controllers/Api/V1/   → All API controllers
    Requests/              → Form Request validation classes
    Resources/             → API Resource response transformers
  Models/                  → Eloquent models with relationships
  Policies/                → Authorization policies (role + object-level)
database/
  migrations/              → Schema definitions
  seeders/                 → Test data generation (planned)
  factories/                → Model factories for bulk seeding (planned)
routes/
  api.php                  → All versioned API routes
docs/
  erd.png (or .pdf)         → Entity Relationship Diagram
postman/
  student-information-api.postman_collection.json
```

## Security Notes

- Passwords are hashed using Laravel's bcrypt-based hashing (never stored or returned in plaintext).
- All protected routes require a valid Sanctum token.
- Role-based and object-level authorization is enforced server-side via Policies on every protected action — not just at the route level.
- Validation is enforced server-side via Form Request classes; invalid data is rejected before it reaches the database.
- Error responses never expose raw database errors, stack traces, or internal file paths — handled centrally in `bootstrap/app.php`.
- CORS and environment secrets are managed via `.env` and excluded from version control.

## AI-Assisted Development Summary

This project was built using AI-assisted development practices in accordance with the lab's AI Accountability policy (Section 3.2). AI was used for:

- Scaffolding migrations, models, controllers, Form Requests, and API Resources following Laravel conventions
- Suggesting validation rules and error-handling patterns
- Debugging real errors encountered during development (e.g., database connection misconfiguration, duplicate/empty migration files, exception handling gaps)
- Drafting this README and API documentation structure

All AI-generated code was reviewed and manually tested via curl against real database state before being accepted, and iterated on when issues were found. For example:

- An early version of the exception handling let raw SQL errors and PHP stack traces leak into API responses (violating the requirement to never expose internal implementation details). This was caught during manual testing and fixed by adding centralized exception handling in `bootstrap/app.php`.
- Duplicate and empty migration files were accidentally created during initial setup (due to terminal paste issues creating repeated `make:model` commands). These were caught by manually inspecting each migration file's contents rather than assuming AI-generated commands had run correctly, then corrected before rebuilding the database.
- Authorization logic — including strict instructor-to-own-offering grade scoping and object-level protection preventing students from viewing other students' records — was manually designed, implemented, and verified through live testing against multiple user roles (admin, registrar, two different instructors, and a student account) before being considered complete.
- A default `.env` database connection name typo (`psql` instead of Laravel's expected `pgsql`) and port/credential mismatches were diagnosed and corrected through iterative testing rather than accepted blindly.

Every endpoint, validation rule, and authorization check in this project has been manually tested with real HTTP requests and can be explained and modified by the developer.