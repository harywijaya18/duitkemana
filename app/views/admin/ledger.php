<?php
$summaryItems = $summaryPagination['items'] ?? [];
$summaryPage = (int) ($summaryPagination['page'] ?? 1);
$perPage = (int) ($summaryPagination['per_page'] ?? 20);
$summaryTotal = (int) ($summaryPagination['total'] ?? 0);
$summaryTotalPages = (int) ($summaryPagination['total_pages'] ?? 1);

$buildSummaryUrl = static function (int $targetPage, string $account = '') use ($targetUser, $startDate, $endDate, $perPage, $detail): string {
    $params = [
        'user_id' => (int) ($targetUser['id'] ?? 0),
        'start_date' => $startDate,
        'end_date' => $endDate,
        'summary_page' => max(1, $targetPage),
        'per_page' => $perPage,
    ];
    if ($account !== '') {
        $params['account'] = $account;
        $params['detail_page'] = (int) (($detail['pagination']['page'] ?? 1));
    }
    return base_url('/admin/ledger?' . http_build_query($params));
};

$buildDetailUrl = static function (int $targetPage) use ($targetUser, $startDate, $endDate, $perPage, $summaryPage, $selectedAccount): string {
    return base_url('/admin/ledger?' . http_build_query([
        'user_id' => (int) ($targetUser['id'] ?? 0),
        'start_date' => $startDate,
        'end_date' => $endDate,
        'summary_page' => $summaryPage,
        'detail_page' => max(1, $targetPage),
        'per_page' => $perPage,
        'account' => $selectedAccount,
    ]));
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1"><?= e(t('Admin Ledger')); ?></h1>
        <p class="mb-0"><?= e(t('Selected User')); ?>: <strong><?= e((string) ($targetUser['name'] ?? '-')); ?></strong> (<?= e((string) ($targetUser['email'] ?? '-')); ?>)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/admin/ledger')); ?>">
            <i class="fa-solid fa-arrow-left me-1"></i><?= e(t('Back to User List')); ?>
        </a>
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/admin/ledger/export?user_id=' . urlencode((string) ($targetUser['id'] ?? 0)) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . ($selectedAccount !== '' ? '&account=' . urlencode($selectedAccount) : ''))); ?>">
            <i class="fa-solid fa-file-excel me-1"></i>.xlsx
        </a>
        <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/admin/journal?user_id=' . urlencode((string) ($targetUser['id'] ?? 0)) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate))); ?>">
            <i class="fa-solid fa-book-journal-whills me-1"></i><?= e(t('Journal')); ?>
        </a>
    </div>
</section>

<section class="admin-panel mb-3">
    <form method="get" action="<?= e(base_url('/admin/ledger')); ?>" class="admin-filter-grid">
        <input type="hidden" name="user_id" value="<?= e((string) ($targetUser['id'] ?? 0)); ?>">
        <?php if ($selectedAccount !== ''): ?>
            <input type="hidden" name="account" value="<?= e($selectedAccount); ?>">
        <?php endif; ?>
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
                <?php foreach ([10, 20, 50, 100] as $option): ?>
                    <option value="<?= e((string) $option); ?>" <?= $perPage === $option ? 'selected' : ''; ?>><?= e((string) $option); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-actions d-flex gap-2 align-items-end">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
        </div>
    </form>
</section>

<section class="admin-panel mb-3">
    <div class="admin-panel-head">
        <h5 class="mb-0"><?= e(t('Ledger Summary')); ?></h5>
        <span class="badge text-bg-light">Page <?= e((string) $summaryPage); ?>/<?= e((string) $summaryTotalPages); ?></span>
    </div>
    <?php if (empty($summaryItems)): ?>
        <p class="text-muted mb-0"><?= e(t('No data in selected range.')); ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle admin-table mb-0">
                <thead>
                    <tr>
                        <th><?= e(t('Account')); ?></th>
                        <th class="text-end"><?= e(t('Debit')); ?></th>
                        <th class="text-end"><?= e(t('Credit')); ?></th>
                        <th class="text-end"><?= e(t('Balance')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summaryItems as $acc): ?>
                        <tr>
                            <td>
                                <a href="<?= e($buildSummaryUrl($summaryPage, (string) ($acc['account_key'] ?? ''))); ?>">
                                    <?= e((string) ($acc['account_name'] ?? '')); ?>
                                </a>
                            </td>
                            <td class="text-end"><?= e(currency_format((float) ($acc['debit_total'] ?? 0))); ?></td>
                            <td class="text-end"><?= e(currency_format((float) ($acc['credit_total'] ?? 0))); ?></td>
                            <td class="text-end"><?= e(currency_format((float) ($acc['balance'] ?? 0))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($summaryTotalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <a class="btn btn-sm btn-outline-secondary <?= $summaryPage <= 1 ? 'disabled' : ''; ?>" href="<?= e($buildSummaryUrl($summaryPage - 1, $selectedAccount)); ?>">Prev</a>
            <small class="text-muted">Page <?= e((string) $summaryPage); ?> of <?= e((string) $summaryTotalPages); ?> (<?= e((string) $summaryTotal); ?> rows)</small>
            <a class="btn btn-sm btn-outline-secondary <?= $summaryPage >= $summaryTotalPages ? 'disabled' : ''; ?>" href="<?= e($buildSummaryUrl($summaryPage + 1, $selectedAccount)); ?>">Next</a>
        </div>
    <?php endif; ?>
</section>

<?php if (!empty($selectedAccount) && !empty($detail)): ?>
    <?php
    $detailPagination = $detail['pagination'] ?? ['items' => [], 'page' => 1, 'total_pages' => 1, 'total' => 0];
    $detailItems = $detailPagination['items'] ?? [];
    $detailPage = (int) ($detailPagination['page'] ?? 1);
    $detailTotalPages = (int) ($detailPagination['total_pages'] ?? 1);
    $detailTotal = (int) ($detailPagination['total'] ?? 0);
    ?>
    <section class="admin-panel">
        <div class="admin-panel-head">
            <h5 class="mb-0"><?= e(t('Ledger Detail')); ?></h5>
            <span class="badge text-bg-light">Page <?= e((string) $detailPage); ?>/<?= e((string) $detailTotalPages); ?></span>
        </div>
        <p class="small text-muted mb-2"><?= e(t('Opening Balance')); ?>: <strong><?= e(currency_format((float) ($detail['opening_balance'] ?? 0))); ?></strong></p>
        <?php if (empty($detailItems)): ?>
            <p class="text-muted mb-0"><?= e(t('No data in selected range.')); ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th><?= e(t('Date')); ?></th>
                            <th><?= e(t('Journal No')); ?></th>
                            <th><?= e(t('Description')); ?></th>
                            <th class="text-end"><?= e(t('Debit')); ?></th>
                            <th class="text-end"><?= e(t('Credit')); ?></th>
                            <th class="text-end"><?= e(t('Running Balance')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailItems as $row): ?>
                            <tr>
                                <td><?= e((string) ($row['entry_date'] ?? '')); ?></td>
                                <td><?= e((string) ($row['journal_no'] ?? '')); ?></td>
                                <td><?= e((string) ($row['description'] ?? '')); ?></td>
                                <td class="text-end"><?= (float) ($row['debit'] ?? 0) > 0 ? e(currency_format((float) ($row['debit'] ?? 0))) : '-'; ?></td>
                                <td class="text-end"><?= (float) ($row['credit'] ?? 0) > 0 ? e(currency_format((float) ($row['credit'] ?? 0))) : '-'; ?></td>
                                <td class="text-end"><?= e(currency_format((float) ($row['running_balance'] ?? 0))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($detailTotalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <a class="btn btn-sm btn-outline-secondary <?= $detailPage <= 1 ? 'disabled' : ''; ?>" href="<?= e($buildDetailUrl($detailPage - 1)); ?>">Prev</a>
                <small class="text-muted">Page <?= e((string) $detailPage); ?> of <?= e((string) $detailTotalPages); ?> (<?= e((string) $detailTotal); ?> rows)</small>
                <a class="btn btn-sm btn-outline-secondary <?= $detailPage >= $detailTotalPages ? 'disabled' : ''; ?>" href="<?= e($buildDetailUrl($detailPage + 1)); ?>">Next</a>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>