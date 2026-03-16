<?php
$items = $pagination['items'] ?? [];
$total = (int) ($pagination['total'] ?? 0);
$page = (int) ($pagination['page'] ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
$statusFilter = trim((string) ($filters['status'] ?? ''));
$queryFilter = trim((string) ($filters['q'] ?? ''));
$createdFromFilter = trim((string) ($filters['created_from'] ?? ''));
$createdToFilter = trim((string) ($filters['created_to'] ?? ''));
$activityFilter = trim((string) ($filters['activity'] ?? ''));
$route = $mode === 'ledger' ? '/admin/ledger' : '/admin/journal';
$exportRoute = $mode === 'ledger' ? '/admin/ledger/export' : '/admin/journal/export';
$title = $mode === 'ledger' ? t('Admin Ledger') : t('Admin Journal');
$openLabel = $mode === 'ledger' ? t('Open Ledger') : t('Open Journal');

$fmtInt = static function ($value): string {
    return number_format((int) $value, 0, ',', '.');
};
$fmtMoney = static function ($value): string {
    return 'IDR ' . number_format((float) $value, 0, ',', '.');
};

$buildPageUrl = static function (int $targetPage, string $status, string $q, string $createdFrom, string $createdTo, string $activity, string $baseRoute, string $startDate, string $endDate): string {
    $params = ['page' => max(1, $targetPage)];
    if ($status !== '') {
        $params['status'] = $status;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($createdFrom !== '') {
        $params['created_from'] = $createdFrom;
    }
    if ($createdTo !== '') {
        $params['created_to'] = $createdTo;
    }
    if ($activity !== '') {
        $params['activity'] = $activity;
    }
    $params['start_date'] = $startDate;
    $params['end_date'] = $endDate;
    return base_url($baseRoute) . '?' . http_build_query($params);
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1"><?= e($title); ?></h1>
        <p class="mb-0"><?= e(t('Choose a registered user, then open the selected accounting view.')); ?></p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-users me-1"></i><?= e($fmtInt($total)); ?> users</span>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h5 class="mb-0"><?= e(t('Registered Users')); ?></h5>
        <span class="badge text-bg-light">Page <?= e((string) $page); ?>/<?= e((string) $totalPages); ?></span>
    </div>

    <form method="get" action="<?= e(base_url($route)); ?>" class="admin-filter-grid mb-3">
        <div>
            <label class="form-label form-label-sm mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
            </select>
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Search</label>
            <input type="text" name="q" value="<?= e($queryFilter); ?>" class="form-control form-control-sm" placeholder="name or email">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Created From</label>
            <input type="date" name="created_from" value="<?= e($createdFromFilter); ?>" class="form-control form-control-sm">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Created To</label>
            <input type="date" name="created_to" value="<?= e($createdToFilter); ?>" class="form-control form-control-sm">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1"><?= e(t('Start Date')); ?></label>
            <input type="date" name="start_date" value="<?= e($startDate); ?>" class="form-control form-control-sm">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1"><?= e(t('End Date')); ?></label>
            <input type="date" name="end_date" value="<?= e($endDate); ?>" class="form-control form-control-sm">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Activity</label>
            <select name="activity" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="inactive" <?= $activityFilter === 'inactive' ? 'selected' : ''; ?>>Inactive (0 tx)</option>
                <option value="low" <?= $activityFilter === 'low' ? 'selected' : ''; ?>>Low (1-10 tx)</option>
                <option value="medium" <?= $activityFilter === 'medium' ? 'selected' : ''; ?>>Medium (11-50 tx)</option>
                <option value="high" <?= $activityFilter === 'high' ? 'selected' : ''; ?>>High (51+ tx)</option>
            </select>
        </div>
        <div class="admin-filter-actions d-flex gap-2 align-items-end">
            <button class="btn btn-primary btn-sm" type="submit">
                <i class="fa-solid fa-filter me-1"></i>Filter
            </button>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url($route)); ?>">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle admin-table mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Status</th>
                    <th class="text-end">Transactions</th>
                    <th class="text-end">Total Expense</th>
                    <th>Last Login</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3"><?= e(t('No users found.')); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($items as $u): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($u['name'] ?? '-')); ?></div>
                                <small class="text-muted"><?= e((string) ($u['email'] ?? '-')); ?></small>
                            </td>
                            <td>
                                <?php if (($u['status'] ?? 'active') === 'suspended'): ?>
                                    <span class="badge text-bg-danger">Suspended</span>
                                <?php else: ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= e($fmtInt($u['tx_count'] ?? 0)); ?></td>
                            <td class="text-end text-danger-emphasis"><?= e($fmtMoney($u['total_expense'] ?? 0)); ?></td>
                            <td>
                                <?php if (!empty($u['last_login_at'])): ?>
                                    <?= e(date('d M Y H:i', strtotime((string) $u['last_login_at']))); ?>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-outline-primary btn-sm" href="<?= e(base_url($route . '?' . http_build_query(['user_id' => (int) ($u['id'] ?? 0), 'start_date' => $startDate, 'end_date' => $endDate]))); ?>">
                                    <?= e($openLabel); ?>
                                </a>
                                <a class="btn btn-outline-success btn-sm" href="<?= e(base_url($exportRoute . '?' . http_build_query(['user_id' => (int) ($u['id'] ?? 0), 'start_date' => $startDate, 'end_date' => $endDate]))); ?>" title="Quick export XLSX">
                                    <i class="fa-solid fa-file-excel"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : ''; ?>"
               href="<?= e($buildPageUrl($page - 1, $statusFilter, $queryFilter, $createdFromFilter, $createdToFilter, $activityFilter, $route, $startDate, $endDate)); ?>">Prev</a>
            <small class="text-muted">Page <?= e((string) $page); ?> of <?= e((string) $totalPages); ?></small>
            <a class="btn btn-sm btn-outline-secondary <?= $page >= $totalPages ? 'disabled' : ''; ?>"
               href="<?= e($buildPageUrl($page + 1, $statusFilter, $queryFilter, $createdFromFilter, $createdToFilter, $activityFilter, $route, $startDate, $endDate)); ?>">Next</a>
        </div>
    <?php endif; ?>
</section>