<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## School Management API

A modern, role-based RESTful API for managing school operations, built with Laravel.

---

### Features
- Centralized authentication for all user roles (admin, teacher, student, parent)
- Role-based access control
- CRUD endpoints for students, teachers, parents, classes, and attendance
- Token-based authentication using Laravel Sanctum
- OpenAPI/Swagger documentation
- CORS enabled for API development

---

### Getting Started

#### 1. Clone the repository
```bash
git clone https://github.com/SeyiAyo/school-management-api.git
cd school-management-api
```

#### 2. Install dependencies
```bash
composer install
```

#### 3. Copy `.env.example` to `.env` and set your environment variables
```bash
cp .env.example .env
```

#### 4. Generate application key
```bash
php artisan key:generate
```

#### 5. Run migrations & seeders
```bash
php artisan migrate --seed
```

#### 6. Start the development server
```bash
php artisan serve
```

---

### Authentication & Roles
- All users authenticate via `/api/login` and `/api/register` (admin only).
- After login, users receive a Bearer token for API access.
- User roles: `admin`, `teacher`, `student`, `parent` (set in the `users` table).
- Role-based access is enforced via policies and middleware.

---

### Example API Usage
**Login:**
```http
POST /api/login
Content-Type: application/json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Protected Route Example:**
```http
GET /api/students
Authorization: Bearer {token}
```

### License
MIT
