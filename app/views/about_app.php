<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0"><?= e(t('About Application')); ?></h4>
    <a href="<?= e(base_url('/profile')); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i><?= e(t('Profile')); ?>
    </a>
</section>

<div class="soft-card about-app-card mb-3">
    <div class="about-app-badge-row">
        <span class="about-app-chip"><?= e(t('App Version')); ?></span>
        <span class="badge text-bg-primary">v1.0</span>
    </div>
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="text-muted small"><?= e(t('App Version')); ?></div>
            <h5 class="mb-0"><?= e($appVersion ?? 'v1.0'); ?></h5>
        </div>
        <div class="about-app-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
    </div>
    <p class="mb-3 about-app-story">
        <?= e(t('This application was created from the concern of a husband who saw financial bookkeeping still being done in Excel.')); ?>
    </p>
    <div class="about-app-note">
        <strong>DuitKemana</strong>
        <span><?= e(t('Built to make personal finance records simpler, tidier, and easier to monitor every day.')); ?></span>
    </div>
</div>
