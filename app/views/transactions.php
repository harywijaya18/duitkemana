<section class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><?= e(t('Transaction History')); ?></h4>
    <a href="<?= e(base_url('/transactions/add')); ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus"></i>
    </a>
</section>

<?php
$monthNames = current_language() === 'en'
    ? [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December']
    : [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
?>

<div class="soft-card mb-3">
    <details id="txFilterPanel">
        <summary class="d-flex align-items-center justify-content-between" style="cursor:pointer;list-style:none">
            <span class="fw-600">
                <i class="fa-solid fa-filter me-1"></i><?= e(t('Filter')); ?>
            </span>
            <small id="txFilterToggleText" class="text-muted"><?= e(t('Expand')); ?></small>
        </summary>

        <form method="get" action="<?= e(base_url('/transactions')); ?>" class="row g-2 align-items-end mt-2">
            <div class="col-6">
                <label class="form-label"><?= e(t('Month')); ?></label>
                <select name="month" class="form-select form-select-sm">
                    <option value=""><?= e(t('All')); ?></option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m; ?>" <?= ((int) ($filters['month'] ?? 0) === $m) ? 'selected' : ''; ?>>
                            <?= e($monthNames[$m]); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label"><?= e(t('Year')); ?></label>
                <input type="number" name="year" class="form-control form-control-sm" min="2000" max="2099"
                       value="<?= e((string) ($filters['year'] ?? '')); ?>" placeholder="2026">
            </div>
            <div class="col-6">
                <label class="form-label"><?= e(t('Start Date')); ?></label>
                <input type="date" name="start_date" class="form-control form-control-sm"
                       value="<?= e((string) ($filters['start_date'] ?? '')); ?>">
            </div>
            <div class="col-6">
                <label class="form-label"><?= e(t('End Date')); ?></label>
                <input type="date" name="end_date" class="form-control form-control-sm"
                       value="<?= e((string) ($filters['end_date'] ?? '')); ?>">
            </div>
            <div class="col-6">
                <label class="form-label"><?= e(t('Category')); ?></label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value=""><?= e(t('All')); ?></option>
                    <?php foreach (($categories ?? []) as $category): ?>
                        <option value="<?= (int) $category['id']; ?>" <?= ((int) ($filters['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : ''; ?>>
                            <?= e($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label"><?= e(t('Payment Method')); ?></label>
                <select name="payment_method_id" class="form-select form-select-sm">
                    <option value=""><?= e(t('All')); ?></option>
                    <?php foreach (($paymentMethods ?? []) as $pm): ?>
                        <option value="<?= (int) $pm['id']; ?>" <?= ((int) ($filters['payment_method_id'] ?? 0) === (int) $pm['id']) ? 'selected' : ''; ?>>
                            <?= e($pm['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label"><?= e(t('Search')); ?></label>
                <input type="text" name="search" class="form-control form-control-sm"
                       value="<?= e((string) ($filters['search'] ?? '')); ?>"
                       placeholder="<?= e(t('Search by note/category/payment')); ?>">
            </div>
            <div class="col-6 d-grid">
                <button type="submit" class="btn btn-primary btn-sm"><?= e(t('Apply Filter')); ?></button>
            </div>
            <div class="col-6 d-grid">
                <a href="<?= e(base_url('/transactions')); ?>" class="btn btn-outline-secondary btn-sm"><?= e(t('Reset')); ?></a>
            </div>
        </form>
    </details>
</div>

<script>
(function () {
    var panel = document.getElementById('txFilterPanel');
    var label = document.getElementById('txFilterToggleText');
    if (!panel || !label) return;

    function syncLabel() {
        label.textContent = panel.open ? '<?= e(t('Minimize')); ?>' : '<?= e(t('Expand')); ?>';
    }

    syncLabel();
    panel.addEventListener('toggle', syncLabel);
})();
</script>

<?php if (empty($transactions)): ?>
    <div class="soft-card text-center text-muted py-4"><?= e(t('No transactions yet.')); ?></div>
<?php else: ?>
    <div class="vstack gap-2">
        <?php foreach ($transactions as $tx): ?>
            <div class="transaction-item align-items-start">
                <div class="tx-icon"><i class="fa-solid <?= e($tx['category_icon']); ?>"></i></div>
                <div class="tx-body">
                    <?php if (!empty($tx['description'])): ?>
                        <span class="d-block tx-note"><?= e($tx['description']); ?></span>
                    <?php endif; ?>
                    <span class="d-block tx-category"><?= e($tx['category_name']); ?></span>
                    <small><?= e($tx['transaction_date']); ?> • <?= e($tx['payment_method_name']); ?></small>
                </div>
                <div class="text-end">
                    <div class="tx-amount"><?= e(currency_format((float) $tx['amount'])); ?></div>
                    <div class="d-flex gap-1 mt-1 justify-content-end">
                        <a href="<?= e(base_url('/transactions/edit?id=' . (int) $tx['id'])); ?>" class="btn btn-light btn-sm">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <form method="post" action="<?= e(base_url('/transactions/delete')); ?>" onsubmit="return confirm('<?= e(t('Delete this transaction?')); ?>');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?= (int) $tx['id']; ?>">
                            <button class="btn btn-outline-danger btn-sm" type="submit">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
