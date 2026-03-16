# Admin MVP Blueprint (Desktop)

Last updated: 2026-03-16

## Progress Legend

- [x] Done
- [ ] Pending

## Current Progress Snapshot

- [x] Desktop admin layout + sidebar navigation
- [x] Route scaffolding for all admin sections
- [x] Dashboard metrics v1
- [x] Recurring scheduler health check endpoint
- [x] User Management functional module (v1: table + filter + suspend/unsuspend)
- [x] Subscription & Billing functional module
- [x] Operations Monitor functional module (v1)
- [x] Admin Journal user drilldown module
- [x] Admin Ledger user drilldown module
- [x] Product Analytics functional module
- [x] Support Center functional module
- [x] Settings functional module
- [x] Admin audit trail for status actions

## Sidebar Information Architecture

1. Dashboard
- Route: /admin/dashboard
- Purpose: ringkasan metrik bisnis + health operasional.

2. User Management
- Route: /admin/users
- Purpose: daftar user, status akun, detail aktivitas, tindakan admin.

3. Subscription & Billing
- Route: /admin/subscriptions
- Purpose: monitor plan, invoice, payment status, churn risk.

4. Operations Monitor
- Route: /admin/operations
- Purpose: scheduler health, queue/job failure, retry ops.

5. Product Analytics
- Route: /admin/analytics
- Purpose: retention, feature adoption, funnel onboarding.

6. Journal
- Route: /admin/journal
- Purpose: pilih user lalu buka jurnal akuntansi per user.

7. Ledger
- Route: /admin/ledger
- Purpose: pilih user lalu buka buku besar per user.

8. Support Center
- Route: /admin/support
- Purpose: tiket, feedback, pengumuman user segment.

9. Settings
- Route: /admin/settings
- Purpose: feature flags, global config, security controls.

## Checklist Per Menu

### Dashboard
- [x] KPI: total users, active users 30d, tx 30d, expense/income 30d, MRR proxy.
- [x] Trend 6 bulan: users, tx, income, expense.
- [x] Recurring scheduler health.
- [x] Top spenders + recent signups.

### User Management
- [x] Table: id, name, email, plan, last_login, status.
- [x] Actions: suspend/unsuspend, reset password, reset API token.
- [x] Filters: status, plan, signup date, activity.

### Subscription & Billing
- [x] Table: customer, plan, cycle, amount, due date, status.
- [x] Alerts: payment failed, grace period, downgrade risk.
- [x] Exports: invoice and payment Excel.

### Operations Monitor
- [x] Recurring generation coverage.
- [x] Failed jobs and retry queue (placeholder-ready).
- [x] API health panel (latency, error ratio).
- [x] Data consistency checks.

### Product Analytics
- [x] Cohort retention D1/D7/D30.
- [x] Feature adoption matrix.
- [x] Time to first transaction.
- [x] Conversion funnel register to active.

### Support Center
- [x] Ticket inbox and SLA.
- [x] Feedback by category.
- [x] Broadcast announcement history.

### Settings
- [x] Feature flag toggles.
- [x] Threshold and limit configuration.
- [x] Admin audit policy and access control.

## 2-Week MVP Delivery Plan

### Week 1
1. [x] Build sidebar layout and route scaffolding.
2. [x] Implement User Management list + status actions + audit log.
3. [x] Implement Operations Monitor with recurring health + job status placeholders.

### Week 2
1. [x] Implement Subscription & Billing table with status filters.
2. [x] Implement Product Analytics basic trends (retention + adoption v1).
3. [x] Implement Support Center ticket board (simple).
4. [x] Add Settings page (feature flags + admin security basics).

## Non-Functional Requirements

- [x] Admin pages are desktop-first and optimized for >= 1200px width.
- [x] Every admin action touching user state must be logged.
- [x] Sensitive actions require confirmation modal.
- [x] KPI queries should be paginated/cached for large tenant volume.

## Open Items (User Management v1)

- [ ] Add plan column source (subscription module not yet available).
- [ ] Expand audit logging to all sensitive admin actions.
