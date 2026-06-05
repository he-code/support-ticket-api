# Support Ticket API

RESTful API for managing support tickets built with Laravel and MySQL.

## Features

- User authentication with Laravel Sanctum
- RESTful CRUD operations
- Ticket ownership authorization
- Form Request validation
- API Resources
- Secure token authentication
- MySQL database integration

## Tech Stack

- Laravel 13
- PHP 8.3+
- SQLite/MySQL
- Laravel Sanctum
- L5 Swagger

## Installation

Clone the repository:

```bash
git clone https://github.com/he-code/support-ticket-api.git
```
Install dependencies:
```bash
composer install
```
Copy environment file:
```bash
cp .env.example .env
```
Generate application key:
```bash
php artisan key:generate
```
Configure database credentials in ```.env```
Run migrations:
```bash
php artisan migrate
```
Seed database:
```bash
php artisan db:seed
```
Start development server:
```bash
php artisan serve
```
## Authentication

This API uses Laravel Sanctum for token authentication.

Register

POST ```/api/register```

Login

POST ```/api/login```

Logout

POST ```/api/logout```

## Tickets Endpoints

| Method | Endpoint          | Description   |
| ------ | ----------------- | ------------- |
| GET    | /api/tickets      | List tickets  |
| POST   | /api/tickets      | Create ticket |
| GET    | /api/tickets/{id} | Show ticket   |
| PUT    | /api/tickets/{id} | Update ticket |
| DELETE | /api/tickets/{id} | Delete ticket |

## Authorization

Users can only update or delete their own tickets.

## Example Request
```bash
{
  "title": "Cannot access email",
  "description": "Outlook keeps disconnecting",
  "priority": "high"
}
``` 
