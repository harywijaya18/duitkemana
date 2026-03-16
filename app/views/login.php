<section class="auth-card mx-auto mt-4">
    <div class="text-center mb-4">
        <h1 class="app-brand">DuitKemana</h1>
        <p class="text-muted mb-0"><?= e(t('Track your money in seconds')); ?></p>
    </div>

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
