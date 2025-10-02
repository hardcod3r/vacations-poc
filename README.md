# Vacation API — README

![PHPStan](https://github.com/hardcod3r/vacations-poc/actions/workflows/phpstan.yml/badge.svg)
![Tests](https://github.com/hardcod3r/vacations-poc/actions/workflows/tests.yml/badge.svg)
> Framework‑light PHP REST API for employee vacation management. Dockerized. PostgreSQL persistence. JWT auth. OpenAPI first.

---

## Purpose & Scope

Two roles:
- **Employees**: submit vacation requests, list their requests, delete own pending requests.
- **Managers**: authenticate, list employees, create/update/delete employees, list pending requests, approve or reject.

Fictional assignment for technical evaluation.

---

## Features mapped to the assignment scenarios

**Authentication & sessions**
- Email/password **login** → short‑lived JWT access token + server‑persisted refresh token id.
- **Refresh** rotates refresh tokens and returns a new access token.
- **Logout** revokes the active refresh token.
- RBAC: `1 = employee`, `100 = manager`.

**Employee**
- Login, list own requests, submit new request, delete own request if pending.

**Manager**
- Login, list employees, create/update/delete employees, list pending vacation requests, approve/reject.

**Validation, errors, logging**
- Request DTO validation with precise `422` errors.
- Consistent JSON problem responses with domain codes.
- Monolog logs in `storage/logs/app.log`.

---
## Stack & Libraries

- **Runtime**: PHP 8.3+, Docker, Nginx + PHP-FPM  
- **HTTP (PSR)**: `nyholm/psr7`, `nyholm/psr7-server`, PSR-15 middleware  
- **Routing**: `nikic/fast-route`  
- **DI Container**: `php-di/php-di`  
- **ORM/DBAL**: Doctrine (`doctrine/orm`), PostgreSQL 16  
- **Migrations/Seeds**: `robmorgan/phinx`  
- **Validation**: Request DTO rules (custom), 422 problem JSON  
- **Auth/JWT**: `firebase/php-jwt` (RS256, `kid`), refresh tokens persisted  
- **Password hashing**: native `password_hash` Argon2id  
- **Rate limiting**: Redis (`predis/predis`) middleware, fixed window  
- **Logging**: `monolog/monolog` → `storage/logs/app.log`  
- **Testing**: `pestphp/pest`  
- **Static analysis**: `phpstan/phpstan`  
- **Formatter**: `laravel/pint` (`composer run format`)  
- **API contract**: OpenAPI (`public/openapi.json`), Postman import link  

---
## Architecture & Design

**Layers**
- **Domain**: Entities (`Employee`, `VacationRequest`), enums (`Role`, `VacationStatus`).
- **Application**: Use cases (`UserManagement`, `LoginEmployee`, `RefreshSession`, `SubmitVacationRequest`, `ApproveVacationRequest`, `RejectVacationRequest`, `DeleteOwnVacationRequest`).
- **Infrastructure**: PSR‑7/15 HTTP stack (FastRoute + middlewares), Doctrine repositories, JWT (RS256), clock, logging.
- **Interface/HTTP**: Controllers/Actions, Request DTOs, Response resources.
- **Boot/DI**: PHP‑DI wiring across services, repos, use cases, middleware.

**Flow**
`Request → ErrorHandler → RateLimit → (Auth → Role) → Router/Controller → Use case → Repository → Response`

**OpenAPI**
- Served at **`/openapi.json`** (ensure `public/openapi.json` exists).
- Import to Postman: [![Import OpenAPI in Postman](https://img.shields.io/badge/Postman-Import%20OpenAPI-orange)](https://www.postman.com/import?url=http%3A%2F%2Flocalhost%3A8080%2Fopenapi.json)

---


## DDD and tactics

Layered, dependency-inverted design:

- **Domain**  
  Pure PHP. Entities, Enums, Domain Services, and Repository *interfaces*. No framework or IO. Enforces invariants like non-overlapping vacations and valid state transitions.

- **Application**  
  Use cases orchestrate domain logic and *ports*. No HTTP, no DB. Ports: `PasswordHasher`, `JwtIssuer`, `CredentialsStore`, `Clock`. Use cases: `LoginEmployee`, `RefreshSession`, `Logout`, `ChangeOwnPassword`, `SetEmployeePassword`, `SubmitVacationRequest`, `ApproveVacationRequest`, `RejectVacationRequest`, `DeleteOwnVacationRequest`, `UserManagement`.

- **Interface**  
  Adapters at the edge: HTTP controllers, request DTOs, response resources, middleware pipeline, route table. Zero business logic. Validation happens on DTOs. Errors translate to consistent JSON envelopes.

- **Infrastructure**  
  Adapters for persistence (Doctrine repositories + mappers), security (Argon2id hasher, Firebase JWT issuer, Doctrine-backed credentials/refresh tokens), system time, and logging. Phinx migrations implement DB constraints.

**Key tactics**
- Ports & Adapters for security and time. Concrete adapters injected via the container.
- Repository pattern with Doctrine implementations hidden behind domain interfaces.
- DTOs for input; resource mappers for output; no leaking of entities over HTTP.
- Central exception mapping for domain/validation failures.
- Middleware chain: error → auth → role guard → controller.

---

## Data model (PostgreSQL) & migrations

- `employees` (unique `email`, unique 7‑digit `employee_code`, `role`).
- `employee_credentials` (`password_hash`, `argon2id`, `status`), `ON DELETE CASCADE`.
- `refresh_tokens` (rotation chain with `rotated_to`, `issued_at`, `expires_at`, `revoked_at`).
- `vacation_requests` (`from_date`, `to_date`, `reason`, `status`, FKs).

**Constraints**
- `CHECK (from_date <= to_date)`.
- **No overlaps** for pending/approved via `EXCLUDE USING gist` with `btree_gist` extension.

**Seeds**
- One manager, several employees, sample vacation requests across states.

---

## Quick start

### Prerequisites
Docker + Compose. GNU Make (recommended).

### Environment
Copy and edit:
```bash
cp .env.example .env
```

Key variables:
```dotenv
DB_DSN=pgsql:host=db;port=5432;dbname=vacation
DB_USERNAME=vacation
DB_PASSWORD=secret

# JWT keys (file paths OR inline)
JWT_PRIVATE_KEY_PATH=/var/www/html/keys/jwt-private.pem
JWT_PUBLIC_KEY_PATH=/var/www/html/keys/jwt-public.pem
# JWT_PRIVATE_KEY_PEM='-----BEGIN PRIVATE KEY-----...'
# JWT_PUBLIC_KEY_PEM='-----BEGIN PUBLIC KEY-----...'

JWT_KID=k1
JWT_ISS=vacation-api
```

### Make commands
```bash
make up          # build & start (auto-generates JWT keys if missing)
make down        # stop & remove containers & volumes
make logs        # tail docker logs
make migrate     # run DB migrations (phinx)
make seed        # seed demo data
make phpstan     # static analysis
make pest        # run tests (Pest)
make fmt         # format code via Laravel Pint (composer "format": "vendor/bin/pint")
make lint        # bundle checks (if mapped)
make regen-keys  # force-regenerate JWT keys
make sh          # shell inside PHP container
```

### First run
```bash
make up
make migrate
make seed
curl -s http://localhost:8080/health
```

API base URL: `http://localhost:8080/api/v1`

---
# CLI Demo

Besides the REST API, the project ships with an **interactive CLI** for developers and demo purposes.  
It is implemented as a Symfony Console command (`menu`) and uses `PhpSchool\CliMenu` with Guzzle HTTP client.

## Run the CLI

```bash
docker compose exec php php app menu
```

You will see a TUI menu:

![CLI main menu](screenshots/cli_1.png)

## Features

- **Auth menu**
  - Login (select employee from seeded list, mint dev JWT)
  - Refresh
  - Logout

- **Employees menu**
  - List employees
  - Show one
  - Create / Update / Delete

- **Vacations menu**
  - Submit vacation
  - List pending
  - List by employee
  - Approve / Reject
  - Delete own

- **System menu**
  - Health check

## Example Screens

- **List employees**

![CLI list employees](screenshots/cli_2.png)

- **List pending vacation requests**

![CLI pending vacations](screenshots/cli_3.png)

---

**Notes**
- CLI stores session in `.cli-session.json` (token, employee_id, role).
- Uses Guzzle for HTTP calls against `API_BASE_URL` (default: `http://nginx/api/v1`).
- Dev-only JWT minting is available via local private key (`JWT_PRIVATE_KEY_PATH`).
---
## Security

**Auth**
- Bearer JWT (RS256, `kid`), issuer `JWT_ISS`.
- Access token TTL 15m. Refresh TTL 30d with rotation.

**Passwords**
- Argon2id (memory 128MB, time 4, threads 2).

**Transport**
- HTTPS in production. Never send tokens over plain HTTP in prod.

**Errors**
- `401` unauthorized, `403` forbidden, `422` validation, `409` conflicts, `429` throttled.

---

## Migrations & Seeds

Phinx-based:
```bash
make migrate
make seed
# or inside container:
docker compose exec php vendor/bin/phinx migrate -e development
docker compose exec php vendor/bin/phinx seed:run -e development
```

`docker/db/init.sql` bootstraps DB extensions/defaults on first up.

---

## Environment (.env)

Minimal:
```dotenv
APP_ENV=dev
DB_DSN=pgsql:host=db;port=5432;dbname=vacation
DB_USERNAME=vacation
DB_PASSWORD=secret
JWT_PRIVATE_KEY_PATH=/var/www/html/keys/jwt-private.pem
JWT_PUBLIC_KEY_PATH=/var/www/html/keys/jwt-public.pem
JWT_KID=k1
JWT_ISS=vacation-api

# Redis throttle
REDIS_HOST=redis
REDIS_PORT=6379
RATE_LIMIT=100
RATE_WINDOW=60
```

JWT keys auto-generated by `make up` if missing (`keys/` folder).

---

## Starting the API

```bash
make up
make logs
# stop:
make down
```

Health: `GET /api/v1/health`  
Logs: `storage/logs/app.log`

---

## API Contract

- OpenAPI file in repo: `public/openapi.json`
- Served from the container: **http://localhost:8080/openapi.json**
- Import to Postman: [![Import OpenAPI in Postman](https://img.shields.io/badge/Postman-Import%20OpenAPI-orange)](https://www.postman.com/import?url=http%3A%2F%2Flocalhost%3A8080%2Fopenapi.json)

---

## Assignment scenarios → endpoints matrix

| Scenario | Method | Endpoint |
|---|---|---|
| Manager login | POST | `/api/v1/auth/login` |
| Manager logout | POST | `/api/v1/auth/logout` |
| List employees | GET | `/api/v1/employees` |
| Create employee | POST | `/api/v1/employees` |
| Update employee (name, email) | PATCH | `/api/v1/employees/{id}` |
| Set employee password | POST | `/api/v1/employees/{id}/password` |
| Delete employee | DELETE | `/api/v1/employees/{id}` |
| List pending requests | GET | `/api/v1/vacations/pending` |
| Approve request | POST | `/api/v1/vacations/{id}/approve` |
| Reject request | POST | `/api/v1/vacations/{id}/reject` |
| Employee login | POST | `/api/v1/auth/login` |
| Employee logout | POST | `/api/v1/auth/logout` |
| List my requests | GET | `/api/v1/vacations/employee/{id}` |
| Submit request | POST | `/api/v1/vacations` |
| Delete own pending request | DELETE | `/api/v1/vacations/{id}` |
| Refresh token | POST | `/api/v1/auth/refresh` |

---

## End-to-end walkthroughs (cURL)

### Manager
```bash
ACCESS=$(curl -s -X POST http://localhost:8080/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"manager@example.com","password":"manager123!"}' | jq -r .access_token)

curl -s -H "Authorization: Bearer $ACCESS" http://localhost:8080/api/v1/employees | jq .
curl -s -H "Authorization: Bearer $ACCESS" http://localhost:8080/api/v1/vacations/pending | jq .

REQ_ID=... # take from pending list
curl -s -X POST -H "Authorization: Bearer $ACCESS" \
  http://localhost:8080/api/v1/vacations/$REQ_ID/approve | jq .

curl -s -X POST -H "Authorization: Bearer $ACCESS" http://localhost:8080/api/v1/auth/logout
```

### Employee
```bash
ACCESS=$(curl -s -X POST http://localhost:8080/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"employee1@example.com","password":"password123!"}' | jq -r .access_token)

EMP_ID=... # your employee id
curl -s -H "Authorization: Bearer $ACCESS" http://localhost:8080/api/v1/vacations/employee/$EMP_ID | jq .

curl -s -X POST -H 'Content-Type: application/json' -H "Authorization: Bearer $ACCESS" \
  -d '{"from_date":"2025-10-20","to_date":"2025-10-24","reason":"family"}' \
  http://localhost:8080/api/v1/vacations | jq .
```

---

## Endpoints overview

| Method | Path | Summary | Tags | Responses |
|---|---|---|---|---|
| POST | `/api/v1/auth/change-password` | Change own password | auth | 200,401,422 |
| POST | `/api/v1/auth/login` | Login | auth | 200,401,422 |
| POST | `/api/v1/auth/logout` | Logout | auth | 200,401,422 |
| POST | `/api/v1/auth/refresh` | Refresh | auth | 200,401,422 |
| GET | `/api/v1/employees` | List Employees | employees | 200,401 |
| POST | `/api/v1/employees` | Create Employee | employees | 201,409,422 |
| DELETE | `/api/v1/employees/{id}` | Delete Employee | employees | 200,404,422 |
| GET | `/api/v1/employees/{id}` | Show Employee | employees | 200,401,404,422 |
| PATCH | `/api/v1/employees/{id}` | Update Employee | employees | 200,404,422 |
| POST | `/api/v1/employees/{id}/password` | Set Employee Password | employees | 200,404 |
| GET | `/api/v1/health` | Health check | health | 200 |
| POST | `/api/v1/vacations` | Submit a Vacation Request | vacations | 201,401,404,409,422 |
| GET | `/api/v1/vacations/employee/{id}` | List Vacation Requests by Employee | vacations | 200,401,422 |
| GET | `/api/v1/vacations/pending` | List Pending Vacation Requests | vacations | 200,401 |
| DELETE | `/api/v1/vacations/{id}` | Delete Vacation Request | vacations | 200,403,404,409,422 |
| POST | `/api/v1/vacations/{id}/approve` | Approve Vacation Request | vacations | 200,404,409,422 |
| POST | `/api/v1/vacations/{id}/reject` | Reject a Vacation Request | vacations | 200,401,404,409,422 |

---

## Error handling

JSON problem format:
```json
{{ "code": "string", "message": "human-readable", "details": {{ "field": ["reason"] }} }}
```
Key statuses: `400, 401, 403, 404, 409, 422, 429, 500`.  
Errors logged to `storage/logs/app.log` with correlation id.

---

## Testing

- Runner: **Pest**.
- Run: `vendor/bin/pest -p` or `make pest` (if mapped).
- Conventions: seeded fixtures, assert status + schema + DB side effects.

---

## Code quality

- **Static analysis**: PHPStan → `make phpstan` or `vendor/bin/phpstan analyse`.
- **Style**: **Laravel Pint** (`laravel/pint` ^1.25). Run `composer run format` (script: `"format": "vendor/bin/pint"`) or `vendor/bin/pint` (and `make fmt` if mapped).
- **Lint**: `make lint` to bundle checks (if provided).

---

## Rate limiting (Throttle)

**What**: fixed‑window limits per client using **Redis**. Default key = **IP + normalized path**.  
**Config**:
```dotenv
REDIS_HOST=redis
REDIS_PORT=6379
RATE_LIMIT=100
RATE_WINDOW=60
```
**Headers**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`  
**Exceeded**: HTTP `429` with domain code `TOO_MANY_REQUESTS`.  
**Per‑user**: place after Auth and key by JWT `sub` when available.  
**Dependency**: `composer require predis/predis:^2.2`

---

## Makefile: JWT keys automation

`make up` depends on `keys` and will auto‑generate RSA keys if missing:
```bash
openssl genrsa -out keys/jwt-private.pem 2048
openssl rsa -in keys/jwt-private.pem -pubout -out keys/jwt-public.pem
chmod 600 keys/jwt-*.pem
```
Use `make regen-keys` to force regeneration.

Add to `.gitignore`:
```
/keys/jwt-private.pem
/keys/jwt-public.pem
```

---

## Repository structure (abridged)

See codebase. Key dirs:
- `src/Domain`, `src/Application`, `src/Infrastructure`, `src/Interface/Http`
- `public/` (front controller and `openapi.json`)
- `docker/` (php, nginx, db boot scripts)
- `tests/`, `storage/`, `keys/`

---

## Production notes

- HTTPS, HSTS at the edge.
- JWT key rotation, secrets manager storage, short TTLs.
- Strict CORS, rate limit auth and mutations.
- DB least‑privilege user, backups, migrations via CI.
- Observability: health endpoint, structured logs, request IDs.