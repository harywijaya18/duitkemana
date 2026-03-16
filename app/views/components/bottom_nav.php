<?php $path = current_path(); ?>
<nav class="bottom-nav">
    <a href="<?= e(base_url('/')); ?>" class="tab-item <?= $path === '/' ? 'active' : ''; ?>">
        <i class="fa-solid fa-house"></i>
        <span><?= e(t('Home')); ?></span>
    </a>
    <a href="<?= e(base_url('/transactions')); ?>" class="tab-item <?= $path === '/transactions' ? 'active' : ''; ?>">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span><?= e(t('Transactions')); ?></span>
    </a>

    <a href="<?= e(base_url('/transactions/add')); ?>" class="tab-item tab-add <?= $path === '/transactions/add' ? 'active' : ''; ?>">
        <i class="fa-solid fa-plus"></i>
        <span><?= e(t('Add')); ?></span>
    </a>

    <a href="<?= e(base_url('/reports')); ?>" class="tab-item <?= str_starts_with($path, '/reports') ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-pie"></i>
        <span><?= e(t('Reports')); ?></span>
    </a>
    <a href="<?= e(base_url('/profile')); ?>" class="tab-item <?= $path === '/profile' ? 'active' : ''; ?>">
        <i class="fa-solid fa-user"></i>
        <span><?= e(t('Profile')); ?></span>
    </a>
</nav>
