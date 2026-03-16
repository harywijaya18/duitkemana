<?php
$user = auth_user();
$path = current_path();
$adminMenu = [
    ['key' => 'dashboard', 'label' => t('Dashboard'), 'icon' => 'fa-gauge-high', 'href' => base_url('/admin/dashboard')],
    ['key' => 'users', 'label' => t('User Management'), 'icon' => 'fa-users', 'href' => base_url('/admin/users')],
    ['key' => 'transactions', 'label' => t('Transactions Audit'), 'icon' => 'fa-receipt', 'href' => base_url('/admin/transactions')],
    ['key' => 'subscriptions', 'label' => t('Subscription & Billing'), 'icon' => 'fa-credit-card', 'href' => base_url('/admin/subscriptions')],
    ['key' => 'operations', 'label' => t('Operations Monitor'), 'icon' => 'fa-heart-pulse', 'href' => base_url('/admin/operations')],
    ['key' => 'journal', 'label' => t('Journal'), 'icon' => 'fa-book-journal-whills', 'href' => base_url('/admin/journal')],
    ['key' => 'ledger', 'label' => t('Ledger'), 'icon' => 'fa-book', 'href' => base_url('/admin/ledger')],
    ['key' => 'analytics', 'label' => t('Product Analytics'), 'icon' => 'fa-chart-line', 'href' => base_url('/admin/analytics')],
    ['key' => 'support', 'label' => t('Support Center'), 'icon' => 'fa-life-ring', 'href' => base_url('/admin/support')],
    ['key' => 'settings', 'label' => t('Settings'), 'icon' => 'fa-sliders', 'href' => base_url('/admin/settings')],
];
?>
<!doctype html>
<html lang="<?= e(current_language()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('Admin Dashboard')); ?> - DuitKemana</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(base_url('/assets/images/favicon-money.svg')); ?>">
    <link rel="shortcut icon" href="<?= e(base_url('/assets/images/favicon-money.svg')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(base_url('/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="admin-page">
<div class="admin-shell">
    <header class="admin-topbar">
        <div class="admin-topbar-inner">
            <div class="admin-brand-wrap">
                <img src="<?= e(base_url('/assets/images/favicon-money.svg')); ?>" alt="DuitKemana" class="admin-brand-icon">
                <div>
                    <div class="admin-brand-title"><?= e(t('DuitKemana Admin')); ?></div>
                    <div class="admin-brand-subtitle"><?= e(t('SaaS Monitoring Console')); ?></div>
                </div>
            </div>
            <div class="admin-topbar-actions">
                <span class="admin-user-pill">
                    <i class="fa-solid fa-user-shield"></i>
                    <?= e($user['email'] ?? 'admin'); ?>
                </span>
                <a class="btn btn-sm btn-outline-light" href="<?= e(base_url('/')); ?>">
                    <i class="fa-solid fa-mobile-screen-button me-1"></i><?= e(t('User View')); ?>
                </a>
                <form method="post" action="<?= e(base_url('/logout')); ?>" class="m-0">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                    <button type="submit" class="btn btn-sm btn-light">
                        <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="admin-container container-fluid py-4">
        <?php if ($message = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($message = flash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="admin-layout-grid">
            <aside class="admin-sidebar">
                <div class="admin-sidebar-head">
                    <div class="admin-sidebar-title"><?= e(t('Admin Navigation')); ?></div>
                    <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="<?= e(t('Toggle sidebar')); ?>" title="<?= e(t('Minimize sidebar')); ?>">
                        <i class="fa-solid fa-angles-left" id="adminSidebarToggleIcon"></i>
                    </button>
                </div>
                <nav class="admin-sidebar-nav">
                    <?php foreach ($adminMenu as $item): ?>
                        <?php
                        $isActive = $path === parse_url($item['href'], PHP_URL_PATH)
                            || ($item['key'] === 'dashboard' && $path === '/admin');
                        ?>
                        <a href="<?= e($item['href']); ?>" class="admin-nav-link <?= $isActive ? 'active' : ''; ?>" title="<?= e($item['label']); ?>">
                            <i class="fa-solid <?= e($item['icon']); ?>"></i>
                            <span><?= e($item['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <section class="admin-content-area">
