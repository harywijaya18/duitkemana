<?php
$items = $result['items'] ?? [];
$total = (int) ($result['total'] ?? 0);
$page = (int) ($result['page'] ?? 1);
$totalPages = (int) ($result['total_pages'] ?? 1);
$plans = $result['plans'] ?? [];
$summary = $result['summary'] ?? [];

$statusFilter = trim((string) ($filters['status'] ?? ''));
$planIdFilter = (int) ($filters['plan_id'] ?? 0);
$queryFilter = trim((string) ($filters['q'] ?? ''));

$fmtInt = static function ($value): string {
    return number_format((int) $value, 0, ',', '.');
};
$fmtMoney = static function ($value): string {
    return number_format((float) $value, 0, ',', '.');
};

$buildUrl = static function (string $basePath, array $extra = []) use ($statusFilter, $planIdFilter, $queryFilter): string {
    $params = [];
    if ($statusFilter !== '') {
        $params['status'] = $statusFilter;
    }
    if ($planIdFilter > 0) {
        $params['plan_id'] = $planIdFilter;
    }
    if ($queryFilter !== '') {
        $params['q'] = $queryFilter;
    }
    foreach ($extra as $k => $v) {
        $params[$k] = $v;
    }

    if (empty($params)) {
        return base_url($basePath);
    }

    return base_url($basePath) . '?' . http_build_query($params);
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1">Subscription & Billing</h1>
        <p class="mb-0">Monitor plan, status subscription, risiko tagihan gagal, dan ekspor data billing.</p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-credit-card me-1"></i><?= e($fmtInt($total)); ?> subscriptions</span>
    </div>
</section>

<?php if (!$billingAvailable): ?>
    <section class="admin-panel mb-3">
        <div class="alert alert-warning mb-0">
            Tabel billing belum tersedia. Jalankan migrasi <strong>database/migrate_subscriptions.sql</strong> untuk mengaktifkan modul ini.
        </div>
    </section>
<?php endif; ?>

<section class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-success-subtle text-success"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="admin-kpi-label">Active</div>
                <div class="admin-kpi-value"><?= e($fmtInt($summary['active'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-info-subtle text-info"><i class="fa-solid fa-hourglass-half"></i></div>
            <div>
                <div class="admin-kpi-label">Trial</div>
                <div class="admin-kpi-value"><?= e($fmtInt($summary['trial'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-warning-subtle text-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <div class="admin-kpi-label">Past Due</div>
                <div class="admin-kpi-value"><?= e($fmtInt($summary['past_due'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-danger-subtle text-danger"><i class="fa-solid fa-circle-xmark"></i></div>
            <div>
                <div class="admin-kpi-label">Failed Invoices</div>
                <div class="admin-kpi-value"><?= e($fmtInt($summary['failed_invoices'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
</section>

<section class="admin-panel mb-3">
    <div class="admin-panel-head">
        <h5 class="mb-0">Subscriptions</h5>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary btn-sm" href="<?= e($buildUrl('/admin/subscriptions/export')); ?>">
                <i class="fa-solid fa-file-excel me-1"></i>Export Excel
            </a>
            <span class="badge text-bg-light">Page <?= e((string) $page); ?>/<?= e((string) $totalPages); ?></span>
        </div>
    </div>

    <form method="get" action="<?= e(base_url('/admin/subscriptions')); ?>" class="admin-filter-grid mb-3">
        <div>
            <label class="form-label form-label-sm mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="trial" <?= $statusFilter === 'trial' ? 'selected' : ''; ?>>Trial</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="grace" <?= $statusFilter === 'grace' ? 'selected' : ''; ?>>Grace</option>
                <option value="past_due" <?= $statusFilter === 'past_due' ? 'selected' : ''; ?>>Past Due</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Plan</label>
            <select name="plan_id" class="form-select form-select-sm">
                <option value="0">All Plans</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?= e((string) $plan['id']); ?>" <?= $planIdFilter === (int) $plan['id'] ? 'selected' : ''; ?>>
                        <?= e((string) $plan['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= e($queryFilter); ?>" placeholder="name, email, plan">
        </div>
        <div class="admin-filter-actions d-flex gap-2 align-items-end">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/admin/subscriptions')); ?>">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle admin-table mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                    <th>Period End</th>
                    <th>Last Invoice</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Belum ada data subscription.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <?php
                        $status = (string) ($row['status'] ?? '');
                        $statusClass = 'text-bg-secondary';
                        if ($status === 'active') {
                            $statusClass = 'text-bg-success';
                        } elseif ($status === 'trial') {
                            $statusClass = 'text-bg-info';
                        } elseif ($status === 'past_due') {
                            $statusClass = 'text-bg-warning';
                        } elseif ($status === 'cancelled') {
                            $statusClass = 'text-bg-dark';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($row['user_name'] ?? '-')); ?></div>
                                <small class="text-muted"><?= e((string) ($row['user_email'] ?? '-')); ?></small>
                            </td>
                            <td><?= e((string) ($row['plan_name'] ?? '-')); ?></td>
                            <td><span class="badge <?= e($statusClass); ?>"><?= e($status); ?></span></td>
                            <td class="text-end"><?= e((string) ($row['currency'] ?? 'IDR')); ?> <?= e($fmtMoney($row['price_monthly'] ?? 0)); ?></td>
                            <td><?= e((string) ($row['current_period_end'] ?? '-')); ?></td>
                            <td><?= e((string) ($row['last_invoice_status'] ?? '-')); ?></td>
                            <td><?= e((string) ($row['last_invoice_due'] ?? '-')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : ''; ?>" href="<?= e($buildUrl('/admin/subscriptions', ['page' => $page - 1])); ?>">Prev</a>
            <small class="text-muted">Page <?= e((string) $page); ?> of <?= e((string) $totalPages); ?></small>
            <a class="btn btn-sm btn-outline-secondary <?= $page >= $totalPages ? 'disabled' : ''; ?>" href="<?= e($buildUrl('/admin/subscriptions', ['page' => $page + 1])); ?>">Next</a>
        </div>
    <?php endif; ?>
</section>
