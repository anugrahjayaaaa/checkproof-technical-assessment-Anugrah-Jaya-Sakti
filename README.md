<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Checkproof Technical Assessment

Laravel REST API project with Sanctum auth and user management.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

## API Base URL

```
http://localhost:8000/api
```

> A Postman collection is available at `docs/checkproof-technical-assessment.postman_collection.json`.

## Authentication (Sanctum)

All authenticated endpoints use Laravel Sanctum with `auth:sanctum` middleware guard. After login, include the token in the `Authorization` header.

Seeded credentials (password: `#Password123`):

| Email                 | Role         |
|-----------------------|--------------|
| admin@example.com     | administrator|
| manager@example.com   | manager      |
| user@example.com      | user         |

### Login

```http
POST /api/login
Content-Type: application/json
```

Body:

```json
{
  "email": "admin@example.com",
  "password": "#Password123"
}
```

Response:

```json
{
  "message": "Login successful.",
  "data": {
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "name": "Administrator",
      "created_at": "2026-01-01T00:00:00.000000Z"
    },
    "token": "sanctum-token"
  }
}
```

Use the returned `token` as `Authorization: Bearer *** for authenticated endpoints.

---

## Endpoints

### List Users

```http
GET /api/users?page=1&search=john&sortBy=created_at
Authorization: Bearer ***
```

Query params:

- `page` (optional): page number, default `1`
- `search` (optional): search by name or email (case-insensitive, partial match)
- `sortBy` (optional): `name`, `email`, or `created_at`, default `created_at` (descending)

Response:

```json
{
  "page": 1,
  "users": [
    {
      "id": 1,
      "email": "john@example.com",
      "name": "John Doe",
      "role": "user",
      "created_at": "2026-01-01T00:00:00.000000Z",
      "orders_count": 3,
      "can_edit": true
    }
  ]
}
```

Notes:
- Only active users are returned.
- `orders_count` reflects total orders (no status filter).
- `can_edit` is role-based:
  - Administrator: can edit any user
  - Manager: can edit users with role `user` only
  - User: can edit themselves only

---

### Create User

```http
POST /api/users
Authorization: Bearer ***
Content-Type: application/json
```

Only `administrator` and `manager` roles can create users.

Body:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

Validation:
- `name`: required, 3–50 characters
- `email`: required, valid email, unique
- `password`: required, minimum 8 characters

Response: `201 Created`

```json
{
  "id": 2,
  "email": "john@example.com",
  "name": "John Doe",
  "created_at": "2026-01-01T00:00:00.000000Z"
}
```

Notes:
- New users are created with default role `user` and `active = true`.
- Two emails are sent on creation: one to the new user, one to the admin. If email sending fails, the user is still created successfully.
- Password is never included in the response.

---

## Testing

```bash
php artisan test
```

Test credentials:
- All test users use password `#Password123`
- Login via `AuthTestHelper::login()` or POST to `/api/login`