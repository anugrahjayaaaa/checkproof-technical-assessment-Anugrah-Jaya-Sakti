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

## Endpoints

### Login

```http
POST /login
Content-Type: application/json
```

Body:

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

Response:

```json
{
  "message": "Login successful.",
  "data": {
    "user": {},
    "token": "sanctum-token"
  }
}
```

Use the returned `token` as `Authorization: Bearer <token>` for authenticated endpoints.

---

### List Users

```http
GET /users?page=1&search=john&sortBy=created_at
Authorization: Bearer {token}
```

Query params:
- `page` (optional): page number, default `1`
- `search` (optional): search by name or email
- `sortBy` (optional): `name`, `email`, or `created_at`, default `created_at`

Response:

```json
{
  "page": 1,
  "users": []
}
```

---

### Create User

```http
POST /users
Authorization: Bearer {token}
Content-Type: application/json
```

Body:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password"
}
```

Response: `201 Created`

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z"
}
```
