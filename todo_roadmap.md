# Product Roadmap DuitKemana

Last updated: 2026-03-16

## Milestone A - Foundation (Done)

- [x] Native PHP MVC architecture
- [x] Authentication (register/login/logout)
- [x] Expense CRUD web module
- [x] Category management
- [x] Monthly budget and warning
- [x] Reports + Chart.js + export
- [x] Mobile-first UI with bottom tab and card grid

## Milestone B - API Layer (Done)

- [x] Token-based API auth
- [x] API CRUD transactions
- [x] API CRUD categories
- [x] API budget endpoints
- [x] API reports summary/charts/export
- [x] API payment methods endpoint
- [x] API profile endpoint
- [x] API receipt upload endpoint (multipart)
- [x] Postman collection for QA

## Milestone C - Product Features (Partially Done)

- [x] Recurring expense scheduler (manage + generate monthly transactions)
- [ ] Budget goals by category
- [ ] Push notification reminders
- [ ] AI spending anomaly detection
- [ ] Multi-currency exchange support
- [ ] Dark mode and theme personalization

## Milestone D - Mobile/API Production Readiness (Planned)

- [ ] Add API pagination and cursor metadata
- [ ] Add endpoint versioning (/api/v1)
- [ ] Add refresh token flow and token expiry
- [ ] Add API rate limiting
- [ ] Add request and error logging
- [ ] Add standardized error codes
- [ ] Add OpenAPI/Swagger spec
- [ ] Add automated API tests (PHPUnit)

## Next Sprint (Prioritized)

1. Add API pagination for transactions and reports.
2. Introduce API versioning (`/api/v1`) and deprecation notes.
3. Add standardized API error response format + error codes.
4. Draft OpenAPI/Swagger spec and connect basic API test pipeline.

## Admin SaaS MVP Blueprint (Desktop)

### Sidebar Menu Structure

1. Dashboard (`/admin/dashboard`)
2. User Management (`/admin/users`)
3. Subscription & Billing (`/admin/subscriptions`)
4. Operations Monitor (`/admin/operations`)
5. Product Analytics (`/admin/analytics`)
6. Support Center (`/admin/support`)
7. Settings (`/admin/settings`)

### Admin MVP Sprint (2 Weeks)

Week 1
1. Finalize sidebar layout, route scaffolding, and access guard.
2. Build User Management table + basic actions (suspend/reset token) + audit trail.
3. Build Operations Monitor with recurring health and failed jobs list.

Week 2
1. Build Subscription & Billing list with status filters and export CSV.
2. Build Product Analytics basic trends (retention and adoption v1).
3. Build Support Center basic ticket list + announcement draft.
4. Build Settings page for feature flags and admin security controls.

## Notes

- Recurring bills are already available via web routes (`/bills`) and generation flow (`/bills/generate`).
- Current API routes are still unversioned (`/api/...`) and need migration path to `/api/v1/...`.
