<?php
$userName = (string) ($profile['name'] ?? 'User');
$userEmail = (string) ($profile['email'] ?? '-');
$userInitial = strtoupper(substr(trim($userName) !== '' ? $userName : $userEmail, 0, 1));
?>

<section class="profile-page-head mb-3">
    <div class="profile-identity-card">
        <div class="profile-identity-avatar"><?= e($userInitial); ?></div>
        <div>
            <div class="profile-identity-label"><?= e(t('Profile')); ?></div>
            <h4 class="mb-1"><?= e($userName); ?></h4>
            <div class="text-muted small"><?= e($userEmail); ?></div>
        </div>
    </div>
</section>

<div class="soft-card profile-section-card mb-3">
    <div class="profile-section-head">
        <div>
            <div class="profile-section-label"><?= e(t('Profile')); ?></div>
            <div class="text-muted small"><?= e(t('Your personal information and preferences.')); ?></div>
        </div>
    </div>
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

<div class="soft-card profile-menu-card mb-3">
    <div class="profile-section-head">
        <div>
            <div class="profile-section-label"><?= e(t('Quick Access')); ?></div>
            <div class="text-muted small"><?= e(t('Open support or app information from here.')); ?></div>
        </div>
    </div>

    <a href="<?= e(base_url('/profile/support-center')); ?>" class="profile-nav-row text-decoration-none text-reset">
        <div>
            <div class="fw-semibold"><?= e(t('Support Center')); ?></div>
            <small class="text-muted d-block mt-1">
                <?= e($supportEnabled ? t('Open support ticket form and see your ticket history.') : t('Support Center is currently unavailable.')); ?>
            </small>
        </div>
        <div class="profile-nav-icon-wrap">
            <i class="fa-solid fa-life-ring"></i>
        </div>
    </a>
    <a href="<?= e(base_url('/profile/about-app')); ?>" class="profile-nav-row text-decoration-none text-reset">
        <div>
            <div class="fw-semibold"><?= e(t('About Application')); ?></div>
            <small class="text-muted d-block mt-1"><?= e(t('View app version and background story.')); ?></small>
        </div>
        <div class="profile-nav-icon-wrap profile-nav-icon-wrap--warm">
            <i class="fa-solid fa-circle-info"></i>
        </div>
    </a>
</div>

<div class="soft-card profile-menu-card mb-3">
    <div class="profile-section-head">
        <div>
            <div class="profile-section-label"><?= e(t('Account')); ?></div>
            <div class="text-muted small"><?= e(t('Session and account actions.')); ?></div>
        </div>
    </div>

    <form method="post" action="<?= e(base_url('/logout')); ?>" class="m-0">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <button type="submit" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span><?= e(t('Logout')); ?></span>
        </button>
    </form>
</div>
