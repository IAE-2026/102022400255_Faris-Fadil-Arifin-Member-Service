# Keanggotaan Service

Standalone Laravel API service for `keanggotaan` / member management in the IAE book-loan workflow.

This is now a personal service repository. Teammates should keep their own service repositories and connect to this service through HTTP endpoints.

## Tech Stack

- PHP 8.3+
- Laravel 13
- MySQL 8.4
- Docker Compose

## Service URLs

From host/Postman:

```text
http://localhost:8001/api/v1
```

From another teammate service running on the shared Docker network:

```env
MEMBER_SERVICE_URL=http://member-service:8000/api/v1
```

Stable service names for the team:

```text
member-service
catalog-service
loan-service
```

## Docker Setup

Create the shared external Docker network once on each laptop:

```bash
docker network create tubes-iae-network
```

Build and start this service:

```bash
docker compose up -d --build
```

Run migrations:

```bash
docker compose exec member-service php artisan migrate
```

Check the service:

```bash
curl http://localhost:8001/
```

Stop containers:

```bash
docker compose down
```

Stop containers and delete this service's MySQL data:

```bash
docker compose down -v
```

## Docker Services

This repository runs only:

- `member-service`: Laravel Keanggotaan API, host port `8001`, container port `8000`
- `mysql-member`: MySQL database, host port `3307`, container port `3306`

Database:

```env
DB_CONNECTION=mysql
DB_HOST=mysql-member
DB_PORT=3306
DB_DATABASE=tubes_iae_keanggotaan
DB_USERNAME=iae_user
DB_PASSWORD=iae_password
```

## API Contract

All API endpoints require JSON over HTTP and the IAE contract header:

```http
Content-Type: application/json
Accept: application/json
X-IAE-KEY: 102022400255
```

The `X-IAE-KEY` value is configured with:

```env
IAE_API_KEY=102022400255
```

Successful API responses use the IAE-T2 response envelope:

```json
{
  "status": "success",
  "message": "Member retrieved successfully",
  "data": {},
  "meta": {
    "service_name": "Member-Service",
    "api_version": "v1"
  }
}
```

Error responses use:

```json
{
  "status": "error",
  "message": "Detail pesan kesalahan...",
  "errors": null
}
```

### Create Member

`POST /api/v1/members`

Request body:

```json
{
  "name": "Budi Santoso",
  "student_number": "SISWA-00001",
  "email": "budi@example.com",
  "phone": "08123456789",
  "address": "Jl. Merdeka No. 1"
}
```

Example:

```bash
curl -X POST http://localhost:8001/api/v1/members \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-IAE-KEY: 102022400255" \
  -d "{\"name\":\"Budi Santoso\",\"student_number\":\"SISWA-00001\",\"email\":\"budi@example.com\",\"phone\":\"08123456789\",\"address\":\"Jl. Merdeka No. 1\"}"
```

New members are created with `status: active`.

### List Members

`GET /api/v1/members`

Optional query parameter:

- `per_page`: default `10`, maximum `100`

Example:

```bash
curl http://localhost:8001/api/v1/members?per_page=10 \
  -H "Accept: application/json" \
  -H "X-IAE-KEY: 102022400255"
```

### Get Member Detail

`GET /api/v1/members/{id}`

Use this endpoint from the peminjaman service to validate member status.

Example:

```bash
curl http://localhost:8001/api/v1/members/1 \
  -H "Accept: application/json" \
  -H "X-IAE-KEY: 102022400255"
```

Important response fields:

```json
{
  "id": 1,
  "status": "active",
  "is_active": true
}
```

## Error Responses

Validation errors return HTTP `422`:

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

Missing members return HTTP `404`:

```json
{
  "status": "error",
  "message": "Member not found",
  "errors": null
}
```

Missing or invalid API keys return HTTP `401`:

```json
{
  "status": "error",
  "message": "Invalid or missing API key",
  "errors": null
}
```

## Teammate Integration

Teammates should not add their services to this repository. Each person should create their own repository and Docker Compose file, then join the same external Docker network:

```yaml
networks:
  iae-network:
    external: true
    name: tubes-iae-network
```

Then services can call each other by Docker service name:

```env
MEMBER_SERVICE_URL=http://member-service:8000/api/v1
MEMBER_SERVICE_KEY=102022400255
BOOK_SERVICE_URL=http://catalog-service:8000/api/v1
LOAN_SERVICE_URL=http://loan-service:8000/api/v1
```

More details are in [docs/service-requirements.md](docs/service-requirements.md).
The AI chat transcript is in [docs/member-history-ai-chat.md](docs/member-history-ai-chat.md).

## Local Non-Docker Setup

Install dependencies:

```bash
composer install
```

Create `.env` and app key:

```bash
copy .env.example .env
php artisan key:generate
```

For local non-Docker development, change `DB_HOST` to your local database host, such as:

```env
DB_HOST=127.0.0.1
```

Run migrations:

```bash
php artisan migrate
```

Start the API:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

## Testing

Run tests locally:

```bash
php artisan test
```

Run tests in Docker:

```bash
docker compose run --rm member-service php artisan test
```
