# Bookkeeping API — Symfony 8.0

REST API backend for managing invoices and quotations. Frontend is Angular (separate project).

---

## Stack

- PHP 8.4 / Symfony 8.0
- Doctrine ORM + MySQL
- JWT Authentication (RSA keypair via LexikJWTAuthenticationBundle)
- Dompdf (server-side PDF storage)
- NelmioCorsBundleBundle (CORS for Angular)

---

## Must-Read Documentation

### Symfony Core
- [Service Container & Autowiring](https://symfony.com/doc/current/service_container.html) — how services.yaml works, how dependencies are injected
- [Service Binding](https://symfony.com/doc/current/service_container/binding.html) — how `bind:` in services.yaml works (used for `$projectDir`)
- [Security](https://symfony.com/doc/current/security.html) — firewalls, access control, password hashing
- [Controllers](https://symfony.com/doc/current/controller.html) — JsonResponse, Request, routing attributes
- [Routing](https://symfony.com/doc/current/routing.html) — `#[Route]` attributes, route parameters, methods
- [Validation](https://symfony.com/doc/current/validation.html) — `#[Assert]` constraints on entities

### Doctrine
- [Doctrine ORM Associations](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html) — ManyToOne, OneToMany, orphanRemoval
- [Lifecycle Callbacks](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#lifecycle-callbacks) — PrePersist, PreUpdate (used for `updated_at`)
- [Migrations](https://symfony.com/doc/current/doctrine.html#migrations-adding-more-fields) — make:migration, migrate, status

### Authentication
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/3.x/Resources/doc/index.md) — full JWT setup, keypair, firewall config, token TTL
- [Symfony json_login](https://symfony.com/doc/current/security/login_link.html) — how the auto-login endpoint works

### Serializer
- [Symfony Serializer](https://symfony.com/doc/current/serializer.html) — serializing entities to JSON
- [Serializer Groups](https://symfony.com/doc/current/serializer.html#using-serialization-groups-attributes) — controlling which fields are exposed per endpoint

### CORS
- [NelmioCorsBundle](https://github.com/nelmio/NelmioCorsBundle) — CORS configuration for Angular frontend

---

## Bonus Topics (read when ready)

### Security Hardening
- [Voters](https://symfony.com/doc/current/security/voters.html) — fine-grained access control (e.g. a user can only edit their own invoices)
- [Rate Limiting](https://symfony.com/doc/current/rate_limiter.html) — protect the login endpoint from brute force attacks
- [CSRF Protection](https://symfony.com/doc/current/security/csrf.html) — relevant if you ever add form-based endpoints

### API Quality
- [API Platform](https://api-platform.com/docs/) — auto-generates REST + GraphQL APIs from entities, worth knowing even if not used
- [Pagination with Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/pagination.html) — paginate large invoice/client lists
- [Symfony HttpFoundation — JsonResponse](https://symfony.com/doc/current/components/http_foundation.html) — full control over response codes and headers

### PDF
- [Dompdf Documentation](https://github.com/dompdf/dompdf/wiki) — options, fonts, page size, headers/footers
- [html2pdf.js (Angular side)](https://ekoopmans.github.io/html2pdf.js/) — client-side PDF generation from Angular templates

### Performance
- [Doctrine Query Builder](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/query-builder.html) — write optimized queries instead of findAll()
- [HTTP Caching](https://symfony.com/doc/current/http_cache.html) — cache API responses
- [Messenger Component](https://symfony.com/doc/current/messenger.html) — queue PDF generation as a background job instead of doing it synchronously

### Testing
- [Symfony Testing](https://symfony.com/doc/current/testing.html) — unit and functional tests
- [API Testing with WebTestCase](https://symfony.com/doc/current/testing.html#functional-tests) — test your endpoints without Postman

### Deployment
- [Symfony Deployment](https://symfony.com/doc/current/deployment.html) — production checklist
- [Environment Variables in Production](https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables) — never commit secrets

---

## API Endpoints (planned)

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | `/api/auth/register` | Public | Register a new user |
| POST | `/api/auth/login` | Public | Get JWT token |
| GET | `/api/clients` | JWT | List clients |
| POST | `/api/clients` | JWT | Create client |
| GET | `/api/clients/{id}` | JWT | Get client |
| PUT | `/api/clients/{id}` | JWT | Update client |
| DELETE | `/api/clients/{id}` | JWT | Delete client |
| GET | `/api/invoices` | JWT | List invoices |
| POST | `/api/invoices` | JWT | Create invoice |
| GET | `/api/invoices/{id}` | JWT | Get invoice |
| PUT | `/api/invoices/{id}` | JWT | Update invoice (draft only) |
| PATCH | `/api/invoices/{id}/status` | JWT | Change invoice status |
| POST | `/api/invoices/{id}/pdf` | JWT | Store PDF from Angular HTML |
| GET | `/api/invoices/{id}/pdf` | JWT | Retrieve stored PDF |
| GET | `/api/quotations` | JWT | List quotations |
| POST | `/api/quotations` | JWT | Create quotation |
| GET | `/api/quotations/{id}` | JWT | Get quotation |
| PUT | `/api/quotations/{id}` | JWT | Update quotation (draft only) |
| PATCH | `/api/quotations/{id}/status` | JWT | Change quotation status |
| PATCH | `/api/quotations/{id}/convert` | JWT | Convert quotation to invoice |
| POST | `/api/quotations/{id}/pdf` | JWT | Store PDF from Angular HTML |
| GET | `/api/quotations/{id}/pdf` | JWT | Retrieve stored PDF |
