# Separate Repo Service Requirements

Each team member must make their own service repository. This repository is only the `member-service` / Keanggotaan service.

## Team Repository Rule

Each person owns one service repo:

```text
member-service repo  -> Keanggotaan
catalog-service repo -> Katalog Buku
loan-service repo    -> Peminjaman
```

Do not put all services into one repository. Services interact through HTTP endpoints.

## Required Docker Network

Every teammate must create the same external Docker network:

```bash
docker network create tubes-iae-network
```

Every `docker-compose.yml` must join that network:

```yaml
networks:
  iae-network:
    external: true
    name: tubes-iae-network
```

Every service should expose HTTP on container port `8000`.

Recommended host ports:

```text
member-service:  8001
catalog-service: 8002
loan-service:    8003
```

## Stable Service Names

Compose service names must stay consistent so cross-repo Docker DNS works:

```text
member-service
catalog-service
loan-service
```

Inside Docker, services call each other with:

```env
MEMBER_SERVICE_URL=http://member-service:8000/api/v1
BOOK_SERVICE_URL=http://catalog-service:8000/api/v1
LOAN_SERVICE_URL=http://loan-service:8000/api/v1
```

From host/Postman, use:

```text
Member service:  http://localhost:8001/api/v1
Catalog service: http://localhost:8002/api/v1
Loan service:    http://localhost:8003/api/v1
```

## Database Rule

Each repo should run or own its own database container. This member-service repo owns only:

```text
tubes_iae_keanggotaan
```

Recommended teammate database names:

```text
tubes_iae_katalog
tubes_iae_peminjaman
```

Do not query another service's database directly. If a service needs data from another service, call its API endpoint.

## API Response Standard

All services should return JSON with this envelope:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

HTTP status rules:

- `200`: successful read/action
- `201`: successful create
- `404`: requested resource not found
- `422`: validation error
- `503`: dependency service unavailable

## Member Service Contract

This repository provides:

```text
GET  /api/v1/members
POST /api/v1/members
GET  /api/v1/members/{id}
```

`GET /api/v1/members/{id}` includes:

```text
status
is_active
```

Loan service must use this endpoint to validate member status.

## Catalog Service Requirements

Expected repo:

```text
catalog-service
```

Required endpoints:

```text
GET  /api/v1/books
POST /api/v1/books
GET  /api/v1/books/{id}
```

Minimum book fields:

```text
id
title
author
isbn
publisher
year
stock
available_stock
created_at
updated_at
```

`GET /api/v1/books/{id}` must include `available_stock` so the loan service can validate availability.

## Loan Service Requirements

Expected repo:

```text
loan-service
```

Required environment variables:

```env
MEMBER_SERVICE_URL=http://member-service:8000/api/v1
BOOK_SERVICE_URL=http://catalog-service:8000/api/v1
```

Required endpoints:

```text
POST /api/v1/loans
GET  /api/v1/loans
GET  /api/v1/loans/{id}
POST /api/v1/loans/{id}/return
```

Minimum loan fields:

```text
id
member_id
book_id
status
borrowed_at
returned_at
created_at
updated_at
```

Loan service rules:

- Before creating a loan, call `GET /api/v1/members/{id}` on member-service.
- Reject the loan if `is_active` is false.
- Before creating a loan, call `GET /api/v1/books/{id}` on catalog-service.
- Reject the loan if `available_stock` is less than `1`.
- Return `503` if member-service or catalog-service cannot be reached.

## Definition Of Done

A service repo is ready when:

- `docker compose up -d --build` starts without errors.
- The service joins `tubes-iae-network`.
- The service is reachable from host/Postman.
- The service can reach dependency services by Docker service name.
- Migrations run successfully against its own database.
- API tests pass inside Docker.
- The service README documents setup, endpoints, and integration URLs.
