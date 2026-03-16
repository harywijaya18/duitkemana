# Admin MVP Blueprint (Desktop)

Last updated: 2026-03-16

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

6. Support Center
- Route: /admin/support
- Purpose: tiket, feedback, pengumuman user segment.

7. Settings
- Route: /admin/settings
- Purpose: feature flags, global config, security controls.

## Suggested Data Blocks Per Menu

### Dashboard
- KPI: total users, active users 30d, tx 30d, expense/income 30d, MRR proxy.
- Trend 6 bulan: users, tx, income, expense.
- Recurring scheduler health.
- Top spenders + recent signups.

### User Management
- Table: id, name, email, plan, last_login, status.
- Actions: suspend/unsuspend, reset password, reset API token.
- Filters: status, plan, signup date, activity.

### Subscription & Billing
- Table: customer, plan, cycle, amount, due date, status.
- Alerts: payment failed, grace period, downgrade risk.
- Exports: invoice and payment CSV.

### Operations Monitor
- Recurring generation coverage.
- Failed jobs and retry queue.
- API health panel (latency, error ratio).
- Data consistency checks.

### Product Analytics
- Cohort retention D1/D7/D30.
- Feature adoption matrix.
- Time to first transaction.
- Conversion funnel register to active.

### Support Center
- Ticket inbox and SLA.
- Feedback by category.
- Broadcast announcement history.

### Settings
- Feature flag toggles.
- Threshold and limit configuration.
- Admin audit policy and access control.

## 2-Week MVP Delivery Plan

### Week 1
1. Build sidebar layout and route scaffolding.
2. Implement User Management list + status actions + audit log.
3. Implement Operations Monitor with recurring health + job status placeholders.

### Week 2
1. Implement Subscription & Billing table with status filters.
2. Implement Product Analytics basic trends (retention + adoption v1).
3. Implement Support Center ticket board (simple).
4. Add Settings page (feature flags + admin security basics).

## Non-Functional Requirements

- Admin pages are desktop-first and optimized for >= 1200px width.
- Every admin action touching user state must be logged.
- Sensitive actions require confirmation modal.
- KPI queries should be paginated/cached for large tenant volume.
