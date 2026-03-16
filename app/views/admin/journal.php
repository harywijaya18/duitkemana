<?php
$items = $pagination['items'] ?? [];
$page = (int) ($pagination['page'] ?? 1);
$perPage = (int) ($pagination['per_page'] ?? 25);
$total = (int) ($pagination['total'] ?? 0);
$totalPages = (int) ($pagination['total_pages'] ?? 1);

$buildPageUrl = static function (int $targetPage) use ($targetUser, $startDate, $endDate, $perPage): string {
    return base_url('/admin/journal?' . http_build_query([
        'user_id' => (int) ($targetUser['id'] ?? 0),
        'start_date' => $startDate,
        'end_date' => $endDate,
        'page' => max(1, $targetPage),
        'per_page' => $perPage,
    ]));
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1"><?= e(t('Admin Journal')); ?></h1>
        <p class="mb-0"><?= e(t('Selected User')); ?>: <strong><?= e((string) ($targetUser['name'] ?? '-')); ?></strong> (<?= e((string) ($targetUser['email'] ?? '-')); ?>)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/admin/journal')); ?>">
            <i class="fa-solid fa-arrow-left me-1"></i><?= e(t('Back to User List')); ?>
        </a>
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/admin/journal/export?user_id=' . urlencode((string) ($targetUser['id'] ?? 0)) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate))); ?>">
            <i class="fa-solid fa-file-excel me-1"></i>.xlsx
        </a>
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/admin/ledger?user_id=' . urlencode((string) ($targetUser['id'] ?? 0)) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate))); ?>">
            <i class="fa-solid fa-book me-1"></i><?= e(t('Ledger')); ?>
        </a>
    </div>
</section>

<section class="admin-panel mb-3">
    <form method="get" action="<?= e(base_url('/admin/journal')); ?>" class="admin-filter-grid">
        <input type="hidden" name="user_id" value="<?= e((string) ($targetUser['id'] ?? 0)); ?>">
        <input type="hidden" name="per_page" value="<?= e((string) $perPage); ?>">
        <div>
            <label class="form-label form-label-sm mb-1"><?= e(t('Start Date')); ?></label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate); ?>">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1"><?= e(t('End Date')); ?></label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate); ?>">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Per Page</label>
            <select name="per_page" class="form-select form-select-sm">
                <?php foreach ([10, 25, 50, 100] as $option): ?>
                    <option value="<?= e((string) $option); ?>" <?= $perPage === $option ? 'selected' : ''; ?>><?= e((string) $option); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-actions d-flex gap-2 align-items-end">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
        </div>
    </form>
</section>

<section class="row g-3 mb-3">
    <div class="col-12 col-md-6">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-primary-subtle text-primary"><i class="fa-solid fa-arrow-down"></i></div>
            <div>
                <div class="admin-kpi-label"><?= e(t('Total Debit')); ?></div>
                <div class="admin-kpi-value"><?= e(currency_format((float) $totalDebit)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-success-subtle text-success"><i class="fa-solid fa-arrow-up"></i></div>
            <div>
                <div class="admin-kpi-label"><?= e(t('Total Credit')); ?></div>
                <div class="admin-kpi-value"><?= e(currency_format((float) $totalCredit)); ?></div>
            </div>
        </div>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h5 class="mb-0"><?= e(t('Journal Entries')); ?></h5>
        <span class="badge text-bg-light">Page <?= e((string) $page); ?>/<?= e((string) $totalPages); ?></span>
    </div>
    <?php if (empty($items)): ?>
        <p class="text-muted mb-0"><?= e(t('No data in selected range.')); ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle admin-table mb-0">
                <thead>
                    <tr>
                        <th><?= e(t('Date')); ?></th>
                        <th><?= e(t('Journal No')); ?></th>
                        <th><?= e(t('Description')); ?></th>
                        <th><?= e(t('Account')); ?></th>
                        <th class="text-end"><?= e(t('Debit')); ?></th>
                        <th class="text-end"><?= e(t('Credit')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['entry_date'] ?? '')); ?></td>
                            <td><?= e((string) ($row['journal_no'] ?? '')); ?></td>
                            <td><?= e((string) ($row['description'] ?? '')); ?></td>
                            <td><?= e((string) ($row['account_name'] ?? '')); ?></td>
                            <td class="text-end"><?= (float) ($row['debit'] ?? 0) > 0 ? e(currency_format((float) ($row['debit'] ?? 0))) : '-'; ?></td>
                            <td class="text-end"><?= (float) ($row['credit'] ?? 0) > 0 ? e(currency_format((float) ($row['credit'] ?? 0))) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : ''; ?>" href="<?= e($buildPageUrl($page - 1)); ?>">Prev</a>
            <small class="text-muted">Page <?= e((string) $page); ?> of <?= e((string) $totalPages); ?> (<?= e((string) $total); ?> rows)</small>
            <a class="btn btn-sm btn-outline-secondary <?= $page >= $totalPages ? 'disabled' : ''; ?>" href="<?= e($buildPageUrl($page + 1)); ?>">Next</a>
        </div>
    <?php endif; ?>
</section>