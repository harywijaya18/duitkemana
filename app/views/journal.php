<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0\"><?= e(t('Journal')); ?></h4>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-success" href="<?= e(base_url('/journal/export?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate))); ?>">
            <i class="fa-solid fa-file-excel me-1"></i>.xlsx
        </a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('/ledger')); ?>">
            <i class="fa-solid fa-book me-1"></i><?= e(t('Ledger')); ?>
        </a>
    </div>
</section>

<div class="soft-card mb-3">
    <form method="get" action="<?= e(base_url('/journal')); ?>" class="row g-2 align-items-end">
        <div class="col-5">
            <label class="form-label"><?= e(t('Start Date')); ?></label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate); ?>">
        </div>
        <div class="col-5">
            <label class="form-label"><?= e(t('End Date')); ?></label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate); ?>">
        </div>
        <div class="col-2 d-grid">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i></button>
        </div>
    </form>
</div>

<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="summary-card summary-card--cyan">
            <span><?= e(t('Total Debit')); ?></span>
            <h6><?= e(currency_format((float) $totalDebit)); ?></h6>
        </div>
    </div>
    <div class="col-6">
        <div class="summary-card summary-card--purple">
            <span><?= e(t('Total Credit')); ?></span>
            <h6><?= e(currency_format((float) $totalCredit)); ?></h6>
        </div>
    </div>
</div>

<div class="soft-card">
    <h6 class="mb-2"><?= e(t('General Journal Entries')); ?></h6>
    <?php if (empty($lines)): ?>
        <p class="text-muted mb-0"><?= e(t('No data in selected range.')); ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
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
                    <?php foreach ($lines as $row): ?>
                        <tr>
                            <td><?= e($row['entry_date']); ?></td>
                            <td><?= e($row['journal_no']); ?></td>
                            <td><?= e($row['description']); ?></td>
                            <td><?= e($row['account_name']); ?></td>
                            <td class="text-end"><?= $row['debit'] > 0 ? e(currency_format((float) $row['debit'])) : '-'; ?></td>
                            <td class="text-end"><?= $row['credit'] > 0 ? e(currency_format((float) $row['credit'])) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
