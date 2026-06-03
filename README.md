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

## Database Structure

### Entity Relationship Overview

```
User ──────────────────────────────────────────────┐
 │                                                  │
 │ OneToMany                                        │ OneToMany
 ▼                                                  ▼
Invoice ──── ManyToOne ──── Client ──── ManyToOne ── Quotation
 │                                                  │
 │ OneToMany                                        │ OneToMany
 ▼                                                  ▼
InvoiceItem                                   QuotationItem
```

Every `Invoice` and `Quotation` is owned by a `User` (the one who created it) and addressed to a `Client`. Items are children of their parent document and are deleted automatically when the parent is updated or removed.

---

### Tables

#### `user`

Represents the accountant or business owner who logs in and manages documents.

| Column            | Type              | Nullable | Notes                           |
|-------------------|-------------------|----------|---------------------------------|
| id                | int (PK)          | No       | Auto-increment                  |
| email             | varchar(180)      | No       | Unique — used as login identity |
| roles             | json              | No       | Always includes `ROLE_USER`     |
| password          | varchar           | No       | Argon2id hash                   |
| name              | varchar(255)      | No       |                                 |
| TVA_number        | int               | Yes      | VAT registration number         |
| phone             | varchar(255)      | Yes      |                                 |
| billing_address   | text              | Yes      |                                 |
| created_at        | datetime          | Yes      | Set on registration             |

---

#### `client`

A customer that invoices and quotations are addressed to. Clients are shared across all users.

| Column          | Type         | Nullable | Notes                   |
|-----------------|--------------|----------|-------------------------|
| id              | int (PK)     | No       | Auto-increment          |
| name            | varchar(255) | No       |                         |
| contact_email   | varchar(255) | Yes      |                         |
| phone           | varchar(255) | Yes      |                         |
| billing_address | text         | Yes      |                         |
| tax_number      | varchar(50)  | Yes      | Client's VAT/tax number |
| created_at      | datetime     | Yes      | Set on creation         |

---

#### `invoice`

A financial document sent to a client requesting payment.

| Column            | Type            | Nullable | Notes                                          |
|-------------------|-----------------|----------|------------------------------------------------|
| id                | int (PK)        | No       | Auto-increment                                 |
| invoice_number    | varchar(255)    | No       | User-defined reference (e.g. `INV-2024-001`)   |
| status            | varchar(20)     | No       | `draft` → `sent` → `paid` or `cancelled`       |
| issue_date        | date            | No       |                                                |
| due_date          | date            | Yes      |                                                |
| subtotal          | decimal(10,2)   | No       | Sum of all `line_total` values                 |
| tax_total         | decimal(10,2)   | No       | Tax percentage applied to subtotal             |
| total             | decimal(15,2)   | No       | `subtotal + (subtotal × tax_total / 100)`      |
| notes             | text            | Yes      |                                                |
| pdf_path          | varchar(255)    | Yes      | Server path to the stored PDF file             |
| pdf_generated_at  | datetime        | Yes      | Timestamp of last PDF generation               |
| updated_at        | datetime        | Yes      | Auto-set by Doctrine lifecycle callback        |
| client_id         | int (FK)        | No       | → `client.id`                                  |
| created_by_id     | int (FK)        | No       | → `user.id`                                    |

---

#### `invoice_item`

A line item within an invoice. Deleted automatically when the invoice is updated (orphan removal).

| Column      | Type          | Nullable | Notes                              |
|-------------|---------------|----------|------------------------------------|
| id          | int (PK)      | No       | Auto-increment                     |
| description | varchar(255)  | No       |                                    |
| quantity    | int           | No       |                                    |
| unit_price  | decimal(10,2) | No       |                                    |
| tax         | decimal(10,2) | Yes      | Per-item tax amount                |
| line_total  | decimal(10,2) | No       | `unit_price × quantity`            |
| invoice_id  | int (FK)      | No       | → `invoice.id`                     |

---

#### `quotation`

A price proposal sent to a client before work begins. Can be converted to an invoice once accepted.

| Column            | Type          | Nullable | Notes                                                   |
|-------------------|---------------|----------|---------------------------------------------------------|
| id                | int (PK)      | No       | Auto-increment                                          |
| quotation_number  | varchar(255)  | No       | User-defined reference (e.g. `QUO-2024-001`)            |
| status            | varchar(20)   | No       | `draft` → `sent` → `accepted`/`rejected` → `converted` |
| issue_date        | date          | No       |                                                         |
| valid_until       | date          | Yes      | Expiry date of the proposal                             |
| subtotal          | decimal(10,2) | No       | Sum of all `line_total` values                          |
| tax_total         | decimal(10,2) | No       | Tax percentage applied to subtotal                      |
| total             | decimal(10,2) | No       | `subtotal + (subtotal × tax_total / 100)`               |
| notes             | text          | Yes      |                                                         |
| pdf_path          | varchar(255)  | Yes      | Server path to the stored PDF file                      |
| pdf_generated_at  | datetime      | Yes      | Timestamp of last PDF generation                        |
| updated_at        | datetime      | Yes      | Auto-set by Doctrine lifecycle callback                 |
| client_id         | int (FK)      | No       | → `client.id`                                           |
| created_by_id     | int (FK)      | No       | → `user.id`                                             |

---

#### `quotation_item`

A line item within a quotation. Deleted automatically when the quotation is updated (orphan removal).

| Column       | Type          | Nullable | Notes                   |
|--------------|---------------|----------|-------------------------|
| id           | int (PK)      | No       | Auto-increment          |
| description  | varchar(255)  | No       |                         |
| quantity     | int           | No       |                         |
| unit_price   | decimal(10,2) | No       |                         |
| tax_rate     | decimal(10,2) | Yes      | Per-item tax rate       |
| line_total   | decimal(10,2) | No       | `unit_price × quantity` |
| quotation_id | int (FK)      | No       | → `quotation.id`        |

---

### Relationships Summary

| From           | To             | Type       | Foreign Key      | Cascade / Notes                        |
|----------------|----------------|------------|------------------|----------------------------------------|
| User           | Invoice        | OneToMany  | `created_by_id`  | Invoices are scoped per user on fetch  |
| User           | Quotation      | OneToMany  | `created_by_id`  | Quotations are scoped per user on fetch|
| Client         | Invoice        | OneToMany  | `client_id`      |                                        |
| Client         | Quotation      | OneToMany  | `client_id`      |                                        |
| Invoice        | InvoiceItem    | OneToMany  | `invoice_id`     | Cascade persist, orphan removal        |
| Quotation      | QuotationItem  | OneToMany  | `quotation_id`   | Cascade persist, orphan removal        |

---

## Controller — Database Interactions

### AuthController

**`POST /api/auth/register`**
- Creates a new `User` row.
- Hashes the password before storing (argon2id in production, bcrypt fallback).
- Sets `created_at` to the current timestamp.
- Returns 409 if the email is already in use.

**`POST /api/auth/login`**
- Handled entirely by the Symfony firewall / Lexik JWT bundle — no controller code runs.
- On success, returns a signed JWT containing the user's email as the identifier.

---

### ClientController

Clients are **not** scoped to a specific user — all authenticated users share the same client list.

| Action   | DB effect                                                         |
|----------|-------------------------------------------------------------------|
| `list`   | `SELECT *` from `client`                                          |
| `show`   | `SELECT` one `client` by id                                       |
| `create` | `INSERT` into `client`, sets `created_at`                         |
| `update` | `UPDATE` any provided fields on the `client` row                  |
| `delete` | `DELETE` the `client` row (cascades to invoices/quotations via FK)|

---

### InvoiceController

Invoice reads are **scoped to the authenticated user** (`WHERE created_by_id = :user`).

| Action          | DB effect                                                                                                                  |
|-----------------|----------------------------------------------------------------------------------------------------------------------------|
| `list`          | `SELECT` invoices where `created_by_id` matches the JWT user                                                              |
| `show`          | `SELECT` one invoice with its items                                                                                        |
| `create`        | `INSERT` invoice + all items; computes `line_total` per item, then recalculates `subtotal` and `total`                    |
| `update`        | Blocked if `status != draft`. Drops all existing items (orphan removal) then re-inserts, recalculates totals              |
| `changeStatus`  | `UPDATE` `status` only. Allowed values: `draft`, `sent`, `paid`, `cancelled`                                              |
| `delete`        | Blocked if `status != draft`. `DELETE` invoice and all its items (orphan removal)                                         |
| `storePdf`      | Passes HTML to `PdfService`, which converts it to PDF, stores the file, and updates `pdf_path` + `pdf_generated_at`      |
| `getPdf`        | Returns the stored PDF file. Returns 404 if no PDF exists or if the PDF is stale (generated before last `updated_at`)    |

**Totals calculation:**
```
line_total  = unit_price × quantity          (per item, set at write time)
subtotal    = sum of all line_total values
total       = subtotal + (subtotal × tax_total / 100)
```

---

### QuotationController

Quotation reads are **scoped to the authenticated user** (`WHERE created_by_id = :user`).

| Action          | DB effect                                                                                                                    |
|-----------------|------------------------------------------------------------------------------------------------------------------------------|
| `list`          | `SELECT` quotations where `created_by_id` matches the JWT user                                                              |
| `show`          | `SELECT` one quotation with its items                                                                                        |
| `create`        | `INSERT` quotation + all items; computes `line_total` per item, then recalculates `subtotal` and `total`                    |
| `update`        | Blocked if `status != draft`. Drops all existing items (orphan removal) then re-inserts, recalculates totals                |
| `changeStatus`  | `UPDATE` `status` only. Allowed values: `draft`, `sent`, `accepted`, `rejected`                                             |
| `convert`       | Blocked if `status != accepted`. Creates a new `Invoice` from the quotation data, copies all items, sets quotation status to `converted` |
| `delete`        | Blocked if `status != draft`. `DELETE` quotation and all its items (orphan removal)                                         |
| `storePdf`      | Same as invoice PDF — converts HTML to PDF, stores file, updates `pdf_path` + `pdf_generated_at`                           |
| `getPdf`        | Returns the stored PDF file. Returns 404 if no PDF exists or if the PDF is stale                                            |

**Convert to invoice** (`PATCH /api/quotations/{id}/convert`) creates a new invoice row with:
- `invoice_number` = `INV-` + the part after `QUO-` in the quotation number
- Same `client`, `notes`, `subtotal`, `tax_total`, `total`
- All `QuotationItem` rows copied as `InvoiceItem` rows
- Quotation status set to `converted` (no further edits possible)

---

## API Reference

All routes require `Authorization: Bearer <token>` unless marked as public.

### Auth

| Method | Endpoint               | Auth | Description                    |
|--------|------------------------|------|--------------------------------|
| POST   | `/api/auth/register`   | No   | Register a user                |
| POST   | `/api/auth/login`      | No   | Login — returns a JWT          |

### Clients

| Method        | Endpoint              | Description         |
|---------------|-----------------------|---------------------|
| GET           | `/api/clients`        | List all clients    |
| POST          | `/api/clients`        | Create a client     |
| GET           | `/api/clients/{id}`   | Get a client        |
| PUT / PATCH   | `/api/clients/{id}`   | Update a client     |
| DELETE        | `/api/clients/{id}`   | Delete a client     |

### Invoices

| Method      | Endpoint                        | Description                                    |
|-------------|---------------------------------|------------------------------------------------|
| GET         | `/api/invoices`                 | List invoices (current user only)              |
| POST        | `/api/invoices`                 | Create an invoice                              |
| GET         | `/api/invoices/{id}`            | Get invoice with items                         |
| PUT / PATCH | `/api/invoices/{id}`            | Update invoice (draft only)                    |
| PATCH       | `/api/invoices/{id}/status`     | Change status (draft/sent/paid/cancelled)      |
| DELETE      | `/api/invoices/{id}`            | Delete invoice (draft only)                    |
| POST        | `/api/invoices/{id}/pdf`        | Generate and store PDF from HTML               |
| GET         | `/api/invoices/{id}/pdf`        | Download the stored PDF                        |

### Quotations

| Method      | Endpoint                          | Description                                        |
|-------------|-----------------------------------|----------------------------------------------------|
| GET         | `/api/quotations`                 | List quotations (current user only)                |
| POST        | `/api/quotations`                 | Create a quotation                                 |
| GET         | `/api/quotations/{id}`            | Get quotation with items                           |
| PUT / PATCH | `/api/quotations/{id}`            | Update quotation (draft only)                      |
| PATCH       | `/api/quotations/{id}/status`     | Change status (draft/sent/accepted/rejected)       |
| PATCH       | `/api/quotations/{id}/convert`    | Convert accepted quotation to invoice              |
| DELETE      | `/api/quotations/{id}`            | Delete quotation (draft only)                      |
| POST        | `/api/quotations/{id}/pdf`        | Generate and store PDF from HTML                   |
| GET         | `/api/quotations/{id}/pdf`        | Download the stored PDF                            |

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
