<section class="mb-3">
    <h4><?= e(t('Profile')); ?></h4>
</section>

<div class="soft-card mb-3">
    <form method="post" action="<?= e(base_url('/profile/update')); ?>" class="vstack gap-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <div>
            <label class="form-label"><?= e(t('Name')); ?></label>
            <input type="text" name="name" class="form-control form-control-lg" value="<?= e($profile['name'] ?? ''); ?>" required>
        </div>
        <div>
            <label class="form-label"><?= e(t('Email')); ?></label>
            <input type="email" class="form-control form-control-lg" value="<?= e($profile['email'] ?? ''); ?>" disabled>
        </div>
        <div>
            <label class="form-label"><?= e(t('Currency')); ?></label>
            <select name="currency" class="form-select form-select-lg">
                <?php
                $supportedCurrencies = defined('EXCHANGE_RATES_IDR') ? array_keys(EXCHANGE_RATES_IDR) : ['IDR','USD','MYR','SGD'];
                foreach ($supportedCurrencies as $curr):
                    $rate = EXCHANGE_RATES_IDR[$curr] ?? 1;
                    $label = $curr;
                    if ($curr !== 'IDR') {
                        $label .= ' (1 ' . $curr . ' ≈ IDR ' . number_format((float)$rate, 0, ',', '.');
                        $label .= ')';
                    }
                ?>
                    <option value="<?= e($curr); ?>" <?= ($profile['currency'] ?? 'IDR') === $curr ? 'selected' : ''; ?>><?= e($label); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text"><?= e(t('Amounts will be displayed in your chosen currency.')); ?></div>
        </div>
        <button class="btn btn-primary btn-lg" type="submit"><?= e(t('Update Profile')); ?></button>
    </form>
</div>

<div class="soft-card">
    <form method="post" action="<?= e(base_url('/logout')); ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <button class="btn btn-outline-danger w-100" type="submit">
            <i class="fa-solid fa-right-from-bracket me-2"></i><?= e(t('Logout')); ?>
        </button>
    </form>
</div>
