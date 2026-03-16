<section class="auth-card mx-auto mt-4">
    <div class="text-center mb-4">
        <div class="auth-brand-title">
            <img src="<?= e(base_url('/assets/images/favicon-money.svg')); ?>" alt="DuitKemana icon" class="auth-brand-icon">
            <h1 class="app-brand mb-0">DuitKemana</h1>
        </div>
        <p class="text-muted mb-0"><?= e(t('Track your money in seconds')); ?></p>
    </div>

    <?php if ($error = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i>
        <strong>Error:</strong> <?= e($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($success = flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>
        <?= e($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('/login')); ?>" class="vstack gap-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <div>
            <label class="form-label"><?= e(t('Email')); ?></label>
            <input type="email" name="email" class="form-control form-control-lg" required autofocus>
        </div>
        <div>
            <label class="form-label"><?= e(t('Password')); ?></label>
            <div class="input-group">
                <input type="password" name="password" id="loginPassword" class="form-control form-control-lg" required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle="loginPassword">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
        </div>
        <button class="btn btn-primary btn-lg w-100" type="submit">
            <i class="fa-solid fa-right-to-bracket me-2"></i><?= e(t('Login')); ?>
        </button>
    </form>

    <p class="text-center mt-3 mb-0"><?= e(t('No account?')); ?>
        <a href="<?= e(base_url('/register')); ?>"><?= e(t('Register')); ?></a>
    </p>
</section>
