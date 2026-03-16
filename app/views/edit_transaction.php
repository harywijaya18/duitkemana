<section class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><?= e(t('Edit Expense')); ?></h4>
    <a href="<?= e(base_url('/transactions')); ?>" class="btn btn-light btn-sm"><?= e(t('Back')); ?></a>
</section>

<form method="post" action="<?= e(base_url('/transactions/update')); ?>" enctype="multipart/form-data" class="soft-card vstack gap-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
    <input type="hidden" name="id" value="<?= (int) $transaction['id']; ?>">

    <div>
        <label class="form-label"><?= e(t('Amount')); ?></label>
        <input type="text" inputmode="numeric" name="amount" id="txAmount" class="form-control form-control-lg fmt-idr" value="<?= e(number_format((float) $transaction['amount'], 0, ',', '.')); ?>" required>
    </div>

    <div>
        <label class="form-label"><?= e(t('Category')); ?></label>
        <select name="category_id" class="form-select form-select-lg" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id']; ?>" <?= (int) $transaction['category_id'] === (int) $category['id'] ? 'selected' : ''; ?>>
                    <?= e($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="form-label"><?= e(t('Date')); ?></label>
        <input type="date" name="transaction_date" class="form-control form-control-lg" value="<?= e($transaction['transaction_date']); ?>" required>
    </div>

    <div>
        <label class="form-label"><?= e(t('Payment Method')); ?></label>
        <select name="payment_method_id" class="form-select form-select-lg" required>
            <?php foreach ($paymentMethods as $pm): ?>
                <option value="<?= (int) $pm['id']; ?>" <?= (int) $transaction['payment_method_id'] === (int) $pm['id'] ? 'selected' : ''; ?>>
                    <?= e($pm['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="form-label"><?= e(t('Description')); ?></label>
        <input type="text" name="description" class="form-control form-control-lg" value="<?= e($transaction['description']); ?>">
    </div>

    <div>
        <label class="form-label"><?= e(t('Upload Receipt (optional)')); ?></label>
        <input type="file" name="receipt_image" class="form-control" accept="image/*">
        <?php if (!empty($transaction['receipt_image'])): ?>
            <small class="text-muted d-block mt-1"><?= e(t('Current file:')); ?> <?= e($transaction['receipt_image']); ?></small>
        <?php endif; ?>
    </div>

    <button class="btn btn-primary btn-lg w-100" type="submit"><?= e(t('Update Expense')); ?></button>
</form>

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
