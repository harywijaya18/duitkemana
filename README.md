# DuitKemana

Mobile-friendly expense tracker built with PHP Native MVC, MySQL, Bootstrap 5, Vanilla JS, AJAX, Chart.js, and FontAwesome.

## Features

- Register, Login, Logout with session security
- Dashboard with daily/monthly summary and insights
- Add/Edit/Delete expense transactions
- Category management
- Monthly budget and low-budget warning (<20%)
- Reports (daily/weekly/monthly/custom) with charts
- Export report to CSV, Excel, and PDF
- Mobile-first UI with grid icon menu + bottom tab navigation + floating add button
- JSON API layer for mobile apps (Android/iOS) without rewriting CRUD logic

## Project Structure

- `app/controllers`
- `app/models`
- `app/views`
- `app/helpers`
- `config`
- `core`
- `public`
- `storage/uploads`
- `database/schema.sql`

## Local Setup (XAMPP)

1. Copy project to `c:/xampp/htdocs/budget`.
2. Create database and tables:
   - Open phpMyAdmin
   - Import `database/schema.sql`
3. Configure DB in `config/database.php`.
4. Open browser:
   - `http://localhost/budget/public`
5. Register a new user and start tracking expenses.

## Hosting Notes

- Works on standard PHP hosting with MySQL.
- Ensure `storage/uploads` is writable by PHP process.
- Recommended PHP version: 8.0+.

## Mobile API (Native PHP)

Base URL:

- `http://localhost/budget/public/api`
- `http://localhost/budget/public/api/v1` (recommended)

Auth endpoints:

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/refresh`

CRUD endpoints:

- Transactions:
   - `GET /transactions?page=1&per_page=20` or `GET /transactions?cursor=<last_id>&per_page=20`
   - `POST /transactions/create`
   - `POST /transactions/update`
   - `POST /transactions/delete`
   - `POST /transactions/upload-receipt` (multipart form-data, field: `receipt`)
- Categories:
   - `GET /categories`
   - `POST /categories/create`
   - `POST /categories/update`
   - `POST /categories/delete`
- Payment Methods:
   - `GET /payment-methods`
- Budget:
   - `GET /budget?month=3&year=2026`
   - `POST /budget/save`
- Reports:
   - `GET /reports/summary?filter=daily|weekly|monthly|custom&start_date=2026-03-01&end_date=2026-03-15&page=1&per_page=20` or use `cursor=<last_id>`
   - `GET /reports/charts?filter=monthly`
   - `GET /reports/export?format=csv|excel|pdf&filter=monthly`
- Profile:
   - `GET /profile/me`
   - `POST /profile/update`

Headers for protected endpoint:

- `Authorization: Bearer <token>`
- `Content-Type: application/json`

Example login payload:

```json
{
   "email": "user@mail.com",
   "password": "secret123",
   "device_name": "android-app"
}
```

Auth success response includes `access_token`, `refresh_token`, `token_type`, `expires_at`, and `refresh_expires_at`.

Example refresh payload:

```json
{
   "refresh_token": "<refresh-token>"
}
```

Example create transaction payload:

```json
{
   "category_id": 1,
   "amount": 25000,
   "payment_method_id": 1,
   "description": "Makan siang",
   "transaction_date": "2026-03-15"
}
```

Example call reports summary:

```http
GET /api/reports/summary?filter=custom&start_date=2026-03-01&end_date=2026-03-15
Authorization: Bearer <token>
```

Response format:

```json
{
   "success": true,
   "message": "OK",
   "code": "SUCCESS",
   "meta": {
      "timestamp": "2026-03-16T05:00:00+00:00",
      "request_id": "2f2f18c7f9d3417bb4c2a6d4",
      "status": 200,
      "api_version": "v1",
      "path": "/budget/public/api/v1/transactions"
   },
   "data": {}
}
```

Error response format:

```json
{
   "success": false,
   "message": "Validation failed",
   "code": "VALIDATION_FAILED",
   "error": {
      "code": "VALIDATION_FAILED",
      "message": "Validation failed",
      "details": ["amount must be > 0"],
      "status": 422
   },
   "errors": ["amount must be > 0"],
   "meta": {
      "timestamp": "2026-03-16T05:00:00+00:00",
      "request_id": "2f2f18c7f9d3417bb4c2a6d4",
      "status": 422,
      "api_version": "v1",
      "path": "/budget/public/api/v1/transactions/create"
   }
}
```

Common API error codes:
- `UNAUTHORIZED`, `AUTH_TOKEN_MISSING`, `AUTH_TOKEN_INVALID`
- `VALIDATION_FAILED`, `AUTH_VALIDATION_FAILED`, `BUDGET_VALIDATION_FAILED`, `TX_VALIDATION_FAILED`
- `NOT_FOUND`, `TX_NOT_FOUND`
- `CONFLICT`, `AUTH_EMAIL_EXISTS`
- `REPORT_INVALID_FORMAT`

Pagination metadata (for `GET /transactions` and `GET /reports/summary`) is returned in `data.pagination`:

```json
{
   "mode": "page|cursor",
   "page": 1,
   "per_page": 20,
   "total": 120,
   "total_pages": 6,
   "cursor": null,
   "next_cursor": 456,
   "has_more": true
}
```

Notes:

- API auth memakai token tabel `api_tokens`.
- Endpoint legacy `/api/...` mengirim header deprecation (`X-API-Deprecated: true`, `Sunset`, dan `Link` ke `/api/v1`).
- API rate limiting global aktif untuk `/api` dan `/api/v1` (default `120` request per `60` detik per IP).
- Ubah limit via env: `API_RATE_LIMIT_MAX` dan `API_RATE_LIMIT_WINDOW_SECONDS`.
- Logging request/error API aktif jika tabel `api_request_logs` tersedia (jalankan `database/migrate_api_logging.sql`).
- Pastikan re-import `database/schema.sql` atau jalankan SQL table `api_tokens` jika DB sudah terlanjur dibuat.
- Import Postman collection dari `docs/DuitKemana_API.postman_collection.json`.
- OpenAPI/Swagger draft tersedia di `docs/openapi.yaml`.
- Roadmap pengembangan ada di `todo_roadmap.md`.
