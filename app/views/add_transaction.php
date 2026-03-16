<section class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><?= e(t('Add Expense')); ?></h4>
    <a href="<?= e(base_url('/transactions')); ?>" class="btn btn-light btn-sm"><?= e(t('Back')); ?></a>
</section>

<?php
$selectedCategoryId = (string) old('category_id');
if ($selectedCategoryId === '') {
    foreach ($categories as $category) {
        $name = strtolower((string) ($category['name'] ?? ''));
        if (str_contains($name, 'food') || str_contains($name, 'makan')) {
            $selectedCategoryId = (string) $category['id'];
            break;
        }
    }
    if ($selectedCategoryId === '' && !empty($categories)) {
        $selectedCategoryId = (string) $categories[0]['id'];
    }
}

$selectedPaymentMethodId = (string) old('payment_method_id');
if ($selectedPaymentMethodId === '') {
    foreach ($paymentMethods as $pm) {
        $name = strtolower((string) ($pm['name'] ?? ''));
        if (str_contains($name, 'cash') || str_contains($name, 'tunai')) {
            $selectedPaymentMethodId = (string) $pm['id'];
            break;
        }
    }
    if ($selectedPaymentMethodId === '' && !empty($paymentMethods)) {
        $selectedPaymentMethodId = (string) $paymentMethods[0]['id'];
    }
}
?>

<form method="post" action="<?= e(base_url('/transactions/store')); ?>" enctype="multipart/form-data" class="soft-card vstack gap-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

    <div>
        <label class="form-label"><?= e(t('Amount')); ?></label>
        <input type="text" inputmode="numeric" name="amount" id="txAmount" class="form-control form-control-lg fmt-idr" placeholder="50.000" value="<?= e(old('amount') ? number_format((float) old('amount'), 0, ',', '.') : ''); ?>" required>
    </div>

    <div>
        <label class="form-label"><?= e(t('Category')); ?></label>
        <select name="category_id" class="form-select form-select-lg" required>
            <option value=""><?= e(t('Select category')); ?></option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id']; ?>" <?= $selectedCategoryId === (string) $category['id'] ? 'selected' : ''; ?>>
                    <?= e($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="form-label"><?= e(t('Date')); ?></label>
        <input type="date" name="transaction_date" class="form-control form-control-lg" value="<?= e(old('transaction_date', date('Y-m-d'))); ?>" required>
    </div>

    <div>
        <label class="form-label"><?= e(t('Payment Method')); ?></label>
        <select name="payment_method_id" class="form-select form-select-lg" required>
            <option value=""><?= e(t('Select payment method')); ?></option>
            <?php foreach ($paymentMethods as $pm): ?>
                <option value="<?= (int) $pm['id']; ?>" <?= $selectedPaymentMethodId === (string) $pm['id'] ? 'selected' : ''; ?>>
                    <?= e($pm['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="form-label"><?= e(t('Description')); ?></label>
        <input type="text" name="description" class="form-control form-control-lg" placeholder="<?= e(t('Optional note')); ?>" value="<?= e(old('description')); ?>">
    </div>

    <div>
        <label class="form-label"><?= e(t('Upload Receipt (optional)')); ?></label>
        <input type="file" name="receipt_image" class="form-control" accept="image/*">
    </div>

    <button class="btn btn-primary btn-lg w-100" type="submit">
        <i class="fa-solid fa-floppy-disk me-2"></i><?= e(t('Save Expense')); ?>
    </button>
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
