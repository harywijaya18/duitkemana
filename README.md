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

Auth endpoints:

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`

CRUD endpoints:

- Transactions:
   - `GET /transactions`
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
   - `GET /reports/summary?filter=daily|weekly|monthly|custom&start_date=2026-03-01&end_date=2026-03-15`
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
   "data": {}
}
```

Notes:

- API auth memakai token tabel `api_tokens`.
- Pastikan re-import `database/schema.sql` atau jalankan SQL table `api_tokens` jika DB sudah terlanjur dibuat.
- Import Postman collection dari `docs/DuitKemana_API.postman_collection.json`.
- Roadmap pengembangan ada di `todo_roadmap.md`.
