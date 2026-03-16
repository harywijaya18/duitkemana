<?php $user = auth_user(); ?>
<!doctype html>
<html lang="<?= e(current_language()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>DuitKemana</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(base_url('/assets/images/favicon-money.svg')); ?>">
    <link rel="shortcut icon" href="<?= e(base_url('/assets/images/favicon-money.svg')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(base_url('/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body data-page="<?= e(trim(current_path(), '/')); ?>">
<div class="app-shell">
    <main class="main-content container-mobile py-3 pb-5">
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
