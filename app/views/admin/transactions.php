<?php
$listing = $snapshot['listing'] ?? ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
$summary = $snapshot['summary'] ?? ['tx_count' => 0, 'total_amount' => 0];
$users = $snapshot['users'] ?? [];
$categories = $snapshot['categories'] ?? [];
$paymentMethods = $snapshot['payment_methods'] ?? [];

$userIdFilter = (int) ($filters['user_id'] ?? 0);
$categoryIdFilter = (int) ($filters['category_id'] ?? 0);
$paymentMethodFilter = (int) ($filters['payment_method_id'] ?? 0);
$qFilter = trim((string) ($filters['q'] ?? ''));
$startDateFilter = trim((string) ($filters['start_date'] ?? ''));
$endDateFilter = trim((string) ($filters['end_date'] ?? ''));
$minAmountFilter = trim((string) ($filters['min_amount'] ?? ''));
$maxAmountFilter = trim((string) ($filters['max_amount'] ?? ''));

$fmtInt = static function ($value): string {
    return number_format((int) $value, 0, ',', '.');
};
$fmtMoney = static function ($value): string {
    return 'IDR ' . number_format((float) $value, 0, ',', '.');
};

$buildUrl = static function (array $extra = []) use ($userIdFilter, $categoryIdFilter, $paymentMethodFilter, $qFilter, $startDateFilter, $endDateFilter, $minAmountFilter, $maxAmountFilter): string {
    $params = [];

    if ($userIdFilter > 0) {
        $params['user_id'] = $userIdFilter;
    }
    if ($categoryIdFilter > 0) {
        $params['category_id'] = $categoryIdFilter;
    }
    if ($paymentMethodFilter > 0) {
        $params['payment_method_id'] = $paymentMethodFilter;
    }
    if ($qFilter !== '') {
        $params['q'] = $qFilter;
    }
    if ($startDateFilter !== '') {
        $params['start_date'] = $startDateFilter;
    }
    if ($endDateFilter !== '') {
        $params['end_date'] = $endDateFilter;
    }
    if ($minAmountFilter !== '') {
        $params['min_amount'] = $minAmountFilter;
    }
    if ($maxAmountFilter !== '') {
        $params['max_amount'] = $maxAmountFilter;
    }

    foreach ($extra as $k => $v) {
        $params[$k] = $v;
    }

    if (empty($params)) {
        return base_url('/admin/transactions');
    }

    return base_url('/admin/transactions') . '?' . http_build_query($params);
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1">Transactions Audit</h1>
        <p class="mb-0">Pengecekan seluruh transaksi user terdaftar dalam satu dashboard admin.</p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-receipt me-1"></i><?= e($fmtInt($summary['tx_count'] ?? 0)); ?> rows</span>
    </div>
</section>

<section class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-primary-subtle text-primary"><i class="fa-solid fa-list"></i></div>
            <div>
                <div class="admin-kpi-label">Filtered Transactions</div>
                <div class="admin-kpi-value"><?= e($fmtInt($summary['tx_count'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-danger-subtle text-danger"><i class="fa-solid fa-money-bill-wave"></i></div>
            <div>
                <div class="admin-kpi-label">Filtered Amount</div>
                <div class="admin-kpi-value" style="font-size:18px;"><?= e($fmtMoney($summary['total_amount'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
</section>

<section class="admin-panel mb-3">
    <div class="admin-panel-head">
        <h5 class="mb-0">Filter Transactions</h5>
        <span class="badge text-bg-light">Page <?= e((string) ($listing['page'] ?? 1)); ?>/<?= e((string) ($listing['total_pages'] ?? 1)); ?></span>
    </div>

    <form method="get" action="<?= e(base_url('/admin/transactions')); ?>" class="admin-filter-grid">
        <div>
            <label class="form-label form-label-sm mb-1">User</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="0">All Users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= e((string) $u['id']); ?>" <?= $userIdFilter === (int) $u['id'] ? 'selected' : ''; ?>>
                        <?= e((string) ($u['name'] ?? '-')); ?> (<?= e((string) ($u['email'] ?? '-')); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="form-label form-label-sm mb-1">Category</label>
            <select name="category_id" class="form-select form-select-sm">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e((string) $cat['id']); ?>" <?= $categoryIdFilter === (int) $cat['id'] ? 'selected' : ''; ?>>
                        <?= e((string) $cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="form-label form-label-sm mb-1">Payment</label>
            <select name="payment_method_id" class="form-select form-select-sm">
                <option value="0">All Methods</option>
                <?php foreach ($paymentMethods as $pm): ?>
                    <option value="<?= e((string) $pm['id']); ?>" <?= $paymentMethodFilter === (int) $pm['id'] ? 'selected' : ''; ?>>
                        <?= e((string) $pm['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="form-label form-label-sm mb-1">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= e($qFilter); ?>" placeholder="name, email, desc">
        </div>

        <div>
            <label class="form-label form-label-sm mb-1">Start Date</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDateFilter); ?>">
        </div>

        <div>
            <label class="form-label form-label-sm mb-1">End Date</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDateFilter); ?>">
        </div>

        <div>
            <label class="form-label form-label-sm mb-1">Min Amount</label>
            <input type="number" step="0.01" name="min_amount" class="form-control form-control-sm" value="<?= e($minAmountFilter); ?>" placeholder="0">
        </div>

        <div>
            <label class="form-label form-label-sm mb-1">Max Amount</label>
            <input type="number" step="0.01" name="max_amount" class="form-control form-control-sm" value="<?= e($maxAmountFilter); ?>" placeholder="1000000">
        </div>

        <div class="admin-filter-actions d-flex gap-2 align-items-end">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-filter me-1"></i>Apply</button>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/admin/transactions')); ?>">Reset</a>
        </div>
    </form>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h5 class="mb-0">Transactions List</h5>
        <span class="badge text-bg-light">Total <?= e($fmtInt($listing['total'] ?? 0)); ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle admin-table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Category</th>
                    <th>Payment</th>
                    <th class="text-end">Amount</th>
                    <th>Description</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($listing['items'])): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Tidak ada transaksi sesuai filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($listing['items'] as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['transaction_date'] ?? '-')); ?></td>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($row['user_name'] ?? '-')); ?></div>
                                <small class="text-muted"><?= e((string) ($row['user_email'] ?? '-')); ?></small>
                            </td>
                            <td><?= e((string) ($row['category_name'] ?? '-')); ?></td>
                            <td><?= e((string) ($row['payment_method_name'] ?? '-')); ?></td>
                            <td class="text-end text-danger-emphasis fw-semibold"><?= e($fmtMoney((float) ($row['amount'] ?? 0))); ?></td>
                            <td>
                                <?php $desc = trim((string) ($row['description'] ?? '')); ?>
                                <?= e($desc !== '' ? $desc : '-'); ?>
                            </td>
                            <td>
                                <?php if (!empty($row['receipt_image'])): ?>
                                    <a href="<?= e(receipt_url((string) $row['receipt_image'])); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary admin-icon-btn" title="Open Receipt">
                                        <i class="fa-solid fa-paperclip"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ((int) ($listing['total_pages'] ?? 1) > 1): ?>
        <?php $page = (int) ($listing['page'] ?? 1); $totalPages = (int) ($listing['total_pages'] ?? 1); ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : ''; ?>" href="<?= e($buildUrl(['page' => $page - 1])); ?>">Prev</a>
            <small class="text-muted">Page <?= e((string) $page); ?> of <?= e((string) $totalPages); ?></small>
            <a class="btn btn-sm btn-outline-secondary <?= $page >= $totalPages ? 'disabled' : ''; ?>" href="<?= e($buildUrl(['page' => $page + 1])); ?>">Next</a>
        </div>
    <?php endif; ?>
</section>
