<section class="auth-card mx-auto mt-3">
    <div class="text-center mb-4">
        <h1 class="app-brand"><?= e(t('Create Account')); ?></h1>
        <p class="text-muted mb-0"><?= e(t('Start tracking today')); ?></p>
    </div>

    <form method="post" action="<?= e(base_url('/register')); ?>" class="vstack gap-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <div>
            <label class="form-label"><?= e(t('Name')); ?></label>
            <input type="text" name="name" class="form-control form-control-lg" value="<?= e(old('name')); ?>" required>
        </div>
        <div>
            <label class="form-label"><?= e(t('Email')); ?></label>
            <input type="email" name="email" class="form-control form-control-lg" value="<?= e(old('email')); ?>" required>
        </div>
        <div>
            <label class="form-label"><?= e(t('Password')); ?></label>
            <div class="input-group">
                <input type="password" name="password" id="registerPassword" class="form-control form-control-lg" required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle="registerPassword">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
        </div>
        <div>
            <label class="form-label"><?= e(t('Currency')); ?></label>
            <select name="currency" class="form-select form-select-lg">
                <option value="IDR" <?= old('currency', 'IDR') === 'IDR' ? 'selected' : ''; ?>>IDR</option>
                <option value="USD" <?= old('currency') === 'USD' ? 'selected' : ''; ?>>USD</option>
                <option value="MYR" <?= old('currency') === 'MYR' ? 'selected' : ''; ?>>MYR</option>
                <option value="SGD" <?= old('currency') === 'SGD' ? 'selected' : ''; ?>>SGD</option>
            </select>
        </div>

        <button class="btn btn-primary btn-lg w-100" type="submit">
            <i class="fa-solid fa-user-plus me-2"></i><?= e(t('Register')); ?>
        </button>
    </form>

    <p class="text-center mt-3 mb-0"><?= e(t('Already registered?')); ?>
        <a href="<?= e(base_url('/login')); ?>"><?= e(t('Login')); ?></a>
    </p>
</section>
