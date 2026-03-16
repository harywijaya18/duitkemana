<?php
$settings = $snapshot['settings'] ?? [];

$boolVal = static function (array $arr, string $key): bool {
    return ((string) ($arr[$key] ?? '0')) === '1';
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1">Admin Settings</h1>
        <p class="mb-0">Atur feature flags dan baseline keamanan admin dari satu panel.</p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-sliders me-1"></i>Configuration Center</span>
    </div>
</section>

<?php if (!$settingsAvailable): ?>
    <section class="admin-panel mb-3">
        <div class="alert alert-warning mb-0">
            Tabel settings belum tersedia. Jalankan migrasi <strong>database/migrate_support_settings.sql</strong>.
        </div>
    </section>
<?php endif; ?>

<section class="admin-panel">
    <form method="post" action="<?= e(base_url('/admin/settings/save')); ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

        <div class="admin-panel-head">
            <h5 class="mb-0">Feature Flags</h5>
            <span class="badge text-bg-light">Runtime toggles</span>
        </div>

        <div class="admin-settings-grid mb-3">
            <label class="admin-setting-item">
                <input class="form-check-input" type="checkbox" name="feature_enable_api_v1" value="1" <?= $boolVal($settings, 'feature_enable_api_v1') ? 'checked' : ''; ?>>
                <div>
                    <div class="fw-semibold">Enable API v1</div>
                    <small class="text-muted">Aktifkan mode endpoint API versi v1.</small>
                </div>
            </label>

            <label class="admin-setting-item">
                <input class="form-check-input" type="checkbox" name="feature_enable_support_center" value="1" <?= $boolVal($settings, 'feature_enable_support_center') ? 'checked' : ''; ?>>
                <div>
                    <div class="fw-semibold">Enable Support Center</div>
                    <small class="text-muted">Aktifkan alur ticketing dan announcement draft.</small>
                </div>
            </label>

            <label class="admin-setting-item">
                <input class="form-check-input" type="checkbox" name="feature_enable_recurring_auto" value="1" <?= $boolVal($settings, 'feature_enable_recurring_auto') ? 'checked' : ''; ?>>
                <div>
                    <div class="fw-semibold">Enable Recurring Auto Process</div>
                    <small class="text-muted">Beri izin scheduler recurring berjalan otomatis.</small>
                </div>
            </label>
        </div>

        <div class="admin-panel-head mt-2">
            <h5 class="mb-0">Security Baseline</h5>
            <span class="badge text-bg-light">Admin policy</span>
        </div>

        <div class="admin-filter-grid mb-3">
            <div>
                <label class="form-label form-label-sm mb-1">Admin Session Timeout (minutes)</label>
                <input type="number" min="5" max="240" name="security_admin_session_timeout_min" class="form-control form-control-sm" value="<?= e((string) ($settings['security_admin_session_timeout_min'] ?? '30')); ?>">
            </div>
            <div>
                <label class="form-label form-label-sm mb-1">Max Failed Login Attempts</label>
                <input type="number" min="3" max="20" name="security_max_failed_login" class="form-control form-control-sm" value="<?= e((string) ($settings['security_max_failed_login'] ?? '5')); ?>">
            </div>
            <div>
                <label class="form-label form-label-sm mb-1">Password Reset TTL (minutes)</label>
                <input type="number" min="5" max="180" name="security_password_reset_ttl_min" class="form-control form-control-sm" value="<?= e((string) ($settings['security_password_reset_ttl_min'] ?? '30')); ?>">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm" <?= !$settingsAvailable ? 'disabled' : ''; ?>>
                <i class="fa-solid fa-floppy-disk me-1"></i>Save Settings
            </button>
        </div>
    </form>
</section>
