<section class="mb-3">
    <h4><?= e(t('Reports')); ?></h4>
</section>

<?php $activeFilter = in_array($filter, ['daily', 'weekly', 'monthly', 'custom'], true) ? $filter : 'monthly'; ?>

<div class="soft-card mb-3">
    <form method="get" action="<?= e(base_url('/reports')); ?>" class="row g-2 align-items-end">
        <div class="col-6">
            <label class="form-label"><?= e(t('Filter')); ?></label>
            <select name="filter" class="form-select" id="filterSelect">
                <option value="daily" <?= $activeFilter === 'daily' ? 'selected' : ''; ?>><?= e(t('Daily')); ?></option>
                <option value="weekly" <?= $activeFilter === 'weekly' ? 'selected' : ''; ?>><?= e(t('Weekly')); ?></option>
                <option value="monthly" <?= $activeFilter === 'monthly' ? 'selected' : ''; ?>><?= e(t('Monthly')); ?></option>
                <option value="custom" <?= $activeFilter === 'custom' ? 'selected' : ''; ?>><?= e(t('Custom range')); ?></option>
            </select>
        </div>
        <div class="col-3 custom-range <?= $activeFilter === 'custom' ? '' : 'd-none'; ?>">
            <label class="form-label"><?= e(t('Start')); ?></label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate); ?>">
        </div>
        <div class="col-3 custom-range <?= $activeFilter === 'custom' ? '' : 'd-none'; ?>">
            <label class="form-label"><?= e(t('End')); ?></label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate); ?>">
        </div>
        <div class="col-12 d-grid">
            <button class="btn btn-primary" type="submit"><?= e(t('Apply Filter')); ?></button>
        </div>
    </form>
</div>

<div class="row g-2 mb-3">
    <div class="col-4"><div class="summary-card"><span><?= e(t('Total')); ?></span><h6><?= e(currency_format((float) $total)); ?></h6></div></div>
    <div class="col-4"><div class="summary-card"><span><?= e(t('Top Category')); ?></span><h6><?= e($topCategory['name'] ?? '-'); ?></h6></div></div>
    <div class="col-4"><div class="summary-card"><span><?= e(t('Avg Daily')); ?></span><h6><?= e(currency_format((float) $avgDaily)); ?></h6></div></div>
</div>

<div class="soft-card mb-3">
    <canvas id="chartCategory" height="180"></canvas>
</div>
<div class="soft-card mb-3">
    <canvas id="chartDaily" height="180"></canvas>
</div>
<div class="soft-card mb-3">
    <canvas id="chartTrend" height="180"></canvas>
</div>

<div id="export-actions" class="soft-card mb-3">
    <h6><?= e(t('Export Data')); ?></h6>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-primary btn-sm" href="<?= e(base_url('/reports/export?format=csv&filter=' . urlencode($activeFilter) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate))); ?>">CSV</a>
        <a class="btn btn-outline-primary btn-sm" href="<?= e(base_url('/reports/export?format=excel&filter=' . urlencode($activeFilter) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate))); ?>">Excel</a>
        <a class="btn btn-outline-primary btn-sm" href="<?= e(base_url('/reports/export?format=pdf&filter=' . urlencode($activeFilter) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate))); ?>">PDF</a>
    </div>
</div>

<div class="soft-card">
    <h6 class="mb-2"><?= e(t('Report Transactions')); ?></h6>
    <?php if (empty($rows)): ?>
        <p class="text-muted mb-0"><?= e(t('No data in selected range.')); ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= e(t('Date')); ?></th>
                        <th><?= e(t('Category')); ?></th>
                        <th><?= e(t('Payment')); ?></th>
                        <th class="text-end"><?= e(t('Amount')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['transaction_date']); ?></td>
                            <td><?= e($row['category_name']); ?></td>
                            <td><?= e($row['payment_method_name']); ?></td>
                            <td class="text-end"><?= e(currency_format((float) $row['amount'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
