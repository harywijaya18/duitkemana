# Todo Roadmap DuitKemana

## Phase 1 - Core Stability

- [x] Native PHP MVC architecture
- [x] Authentication (register/login/logout)
- [x] Expense CRUD web module
- [x] Category management
- [x] Monthly budget and warning
- [x] Reports + Chart.js + export
- [x] Mobile-first UI with bottom tab and card grid

## Phase 2 - Mobile API Layer

- [x] Token-based API auth
- [x] API CRUD transactions
- [x] API CRUD categories
- [x] API budget endpoints
- [x] API reports summary/charts/export
- [x] API payment methods endpoint
- [x] API profile endpoint
- [x] API receipt upload endpoint (multipart)
- [x] Postman collection for QA

## Phase 3 - Android/iOS Readiness

- [ ] Add API pagination and cursor metadata
- [ ] Add endpoint versioning (/api/v1)
- [ ] Add refresh token flow and token expiry
- [ ] Add API rate limiting
- [ ] Add request and error logging
- [ ] Add standardized error codes
- [ ] Add OpenAPI/Swagger spec
- [ ] Add automated API tests (PHPUnit)

## Phase 4 - Product Enhancements

- [ ] Recurring expense scheduler
- [ ] Budget goals by category
- [ ] Push notification reminders
- [ ] AI spending anomaly detection
- [ ] Multi-currency exchange support
- [ ] Dark mode and theme personalization

## Immediate Next Sprint

1. Implement pagination on transactions and reports API.
2. Add API versioning and deprecation strategy.
3. Create Swagger docs and CI test pipeline.
4. Build Flutter/React Native starter client consuming current API.
