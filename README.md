# Bookkeeping Project

A bookkeeping web application with a Symfony REST API backend and an Angular frontend.

---

## Stack

| Layer    | Technology                          |
|----------|-------------------------------------|
| Backend  | PHP 8.4, Symfony 8.0                |
| Database | MariaDB 10.11.2                     |
| Auth     | JWT (LexikJWTAuthenticationBundle)  |
| Frontend | Angular 20.3                        |
| Styling  | Tailwind CSS 4                      |

---

## Prerequisites

Make sure the following are installed before starting:

| Tool           | Minimum version | Notes                                      |
|----------------|-----------------|--------------------------------------------|
| PHP            | 8.4             | With extensions: pdo_mysql, intl, openssl  |
| Composer       | 2.x             | [getcomposer.org](https://getcomposer.org) |
| Symfony CLI    | latest          | [symfony.com/download](https://symfony.com/download) |
| MariaDB        | 10.11           | Running on port **3307**                   |
| Node.js        | 22 LTS          |                                            |
| npm            | 10+             | Comes with Node.js                         |
| Angular CLI    | 20.x            | `npm install -g @angular/cli`              |

---

## Project Structure

```
bookkeepingProject/
├── backend-symf/   # Symfony REST API
└── frontend/       # Angular SPA
```

---

## Backend Setup

### 1. Install dependencies

```bash
cd backend-symf
composer install
```

### 2. Configure environment

Copy and edit the environment file:

```bash
cp .env .env.local
```

Open `.env.local` and set the following:

```env
APP_SECRET=<generate a random 32-char hex string>

# Adjust user, password and port to match your local MariaDB instance
DATABASE_URL="mysql://root:root@127.0.0.1:3307/bookkeeping?serverVersion=10.11.2-MariaDB&charset=utf8mb4"

JWT_PASSPHRASE=<your passphrase>
```

### 3. Create the database

```bash
php bin/console doctrine:database:create
```

### 4. Run migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 5. Generate JWT keys

Skip this step if `config/jwt/private.pem` and `config/jwt/public.pem` already exist.

```bash
php bin/console lexik:jwt:generate-keypair
```

### 6. Start the backend server

```bash
symfony server:start
```

The API will be available at `http://localhost:8000`.

---

## Frontend Setup

### 1. Install dependencies

```bash
cd frontend
npm install
```

### 2. Start the dev server

```bash
npm start
```

The app will be available at `http://localhost:4200`.

---

## API Overview

All API routes are prefixed with `/api`.

| Method | Endpoint                       | Auth required | Description              |
|--------|--------------------------------|---------------|--------------------------|
| POST   | `/api/auth/register`           | No            | Register a new user      |
| POST   | `/api/auth/login`              | No            | Login and receive a JWT  |
| GET    | `/api/clients`                 | Yes           | List clients             |
| POST   | `/api/clients`                 | Yes           | Create a client          |
| GET    | `/api/clients/{id}`            | Yes           | Get a client             |
| PUT    | `/api/clients/{id}`            | Yes           | Update a client          |
| DELETE | `/api/clients/{id}`            | Yes           | Delete a client          |
| GET    | `/api/invoices`                | Yes           | List invoices            |
| POST   | `/api/invoices`                | Yes           | Create an invoice        |
| GET    | `/api/invoices/{id}`           | Yes           | Get an invoice           |
| PUT    | `/api/invoices/{id}`           | Yes           | Update an invoice        |
| DELETE | `/api/invoices/{id}`           | Yes           | Delete an invoice        |
| GET    | `/api/invoices/{id}/pdf`       | Yes           | Download invoice as PDF  |
| GET    | `/api/quotations`              | Yes           | List quotations          |
| POST   | `/api/quotations`              | Yes           | Create a quotation       |
| GET    | `/api/quotations/{id}`         | Yes           | Get a quotation          |
| PUT    | `/api/quotations/{id}`         | Yes           | Update a quotation       |
| DELETE | `/api/quotations/{id}`         | Yes           | Delete a quotation       |

Authenticated requests must include the JWT in the `Authorization` header:

```
Authorization: Bearer <token>
```

---

## Useful Commands

### Backend

```bash
# Clear cache
php bin/console cache:clear

# Create a new migration after entity changes
php bin/console doctrine:migrations:diff

# Apply pending migrations
php bin/console doctrine:migrations:migrate

# Check Symfony requirements
symfony check:requirements
```

### Frontend

```bash
# Run unit tests
npm test

# Build for production
npm run build
```
