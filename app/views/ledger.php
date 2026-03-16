<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0\"><?= e(t('Ledger')); ?></h4>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-success" href="<?= e(base_url('/ledger/export?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . ($selectedAccount !== '' ? '&account=' . urlencode($selectedAccount) : ''))); ?>">
            <i class="fa-solid fa-file-excel me-1"></i>.xlsx
        </a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('/journal')); ?>">
            <i class="fa-solid fa-book-journal-whills me-1"></i><?= e(t('Journal')); ?>
        </a>
    </div>
</section>

<div class="soft-card mb-3">
    <form method="get" action="<?= e(base_url('/ledger')); ?>" class="row g-2 align-items-end">
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

<div class="soft-card mb-3">
    <h6 class="mb-2"><?= e(t('Ledger Summary')); ?></h6>
    <?php if (empty($summary)): ?>
        <p class="text-muted mb-0"><?= e(t('No data in selected range.')); ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(t('Account')); ?></th>
                        <th class="text-end"><?= e(t('Debit')); ?></th>
                        <th class="text-end"><?= e(t('Credit')); ?></th>
                        <th class="text-end"><?= e(t('Balance')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $acc): ?>
                        <tr>
                            <td>
                                <a href="<?= e(base_url('/ledger?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . '&account=' . urlencode($acc['account_key']))); ?>">
                                    <?= e($acc['account_name']); ?>
                                </a>
                            </td>
                            <td class="text-end"><?= e(currency_format((float) $acc['debit_total'])); ?></td>
                            <td class="text-end"><?= e(currency_format((float) $acc['credit_total'])); ?></td>
                            <td class="text-end"><?= e(currency_format((float) $acc['balance'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($selectedAccount) && !empty($detail)): ?>
    <div class="soft-card">
        <h6 class="mb-2"><?= e(t('Ledger Detail')); ?></h6>
        <p class="small text-muted mb-2"><?= e(t('Opening Balance')); ?>: <strong><?= e(currency_format((float) $detail['opening_balance'])); ?></strong></p>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
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
                    <?php foreach ($detail['rows'] as $row): ?>
                        <tr>
                            <td><?= e($row['entry_date']); ?></td>
                            <td><?= e($row['journal_no']); ?></td>
                            <td><?= e($row['description']); ?></td>
                            <td class="text-end"><?= $row['debit'] > 0 ? e(currency_format((float) $row['debit'])) : '-'; ?></td>
                            <td class="text-end"><?= $row['credit'] > 0 ? e(currency_format((float) $row['credit'])) : '-'; ?></td>
                            <td class="text-end"><?= e(currency_format((float) $row['running_balance'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
