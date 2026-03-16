<section class="mb-3">
    <h4><?= e(t('Monthly Budget')); ?></h4>
</section>

<div class="soft-card mb-3">
    <form method="post" action="<?= e(base_url('/budget/save')); ?>" class="row g-2 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <div class="col-4">
            <label class="form-label"><?= e(t('Month')); ?></label>
            <input type="number" name="month" class="form-control" min="1" max="12" value="<?= (int) $month; ?>" required>
        </div>
        <div class="col-4">
            <label class="form-label"><?= e(t('Year')); ?></label>
            <input type="number" name="year" class="form-control" min="2000" value="<?= (int) $year; ?>" required>
        </div>
        <div class="col-4">
            <label class="form-label"><?= e(t('Budget Amount')); ?></label>
            <input type="text" inputmode="numeric" name="amount" class="form-control fmt-idr" value="<?= e($budgetAmount > 0 ? number_format((float) $budgetAmount, 0, ',', '.') : ''); ?>" placeholder="0" required>
        </div>
        <div class="col-12 d-grid">
            <button class="btn btn-primary" type="submit"><?= e(t('Save Budget')); ?></button>
        </div>
    </form>
</div>

<script>
function fmtIdr(n) { return n === '' ? '' : Number(n).toLocaleString('id-ID', {maximumFractionDigits:0}); }
function stripIdr(v) { return String(v).replace(/\./g,'').replace(/[^0-9]/g,''); }
function bindFmtIdr(el) {
    if (!el) return;
    el.addEventListener('focus', () => { el.value = stripIdr(el.value); });
    el.addEventListener('blur',  () => { const n = stripIdr(el.value); el.value = n ? fmtIdr(n) : ''; });
    if (el.value) el.value = fmtIdr(stripIdr(el.value));
}
document.querySelectorAll('.fmt-idr').forEach(bindFmtIdr);
document.querySelector('form')?.addEventListener('submit', function() {
    this.querySelectorAll('.fmt-idr').forEach(el => { el.value = stripIdr(el.value); });
});
</script>

<div class="row g-3">
    <div class="col-6">
        <div class="summary-card">
            <span><?= e(t('Used Budget')); ?></span>
            <h6><?= e(currency_format((float) $used)); ?></h6>
        </div>
    </div>
    <div class="col-6">
        <div class="summary-card <?= $remainingPct < 20 ? 'summary-warning' : ''; ?>">
            <span><?= e(t('Remaining')); ?></span>
            <h6><?= e(currency_format((float) $remaining)); ?></h6>
        </div>
    </div>
</div>

<?php if ($remainingPct < 20): ?>
    <div class="alert alert-warning mt-3 mb-0">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <?= e(t('Remaining budget is less than 20%.')); ?>
    </div>
<?php endif; ?>
