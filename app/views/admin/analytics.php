<?php
$months = (int) ($snapshot['months'] ?? 6);
$retention = $snapshot['retention_overview'] ?? [];
$cohorts = $snapshot['cohorts'] ?? [];
$adoption = $snapshot['adoption'] ?? ['total_users' => 0, 'items' => []];
$activation = $snapshot['activation'] ?? [];
$funnel = $snapshot['funnel'] ?? ['steps' => []];

$fmtInt = static function ($value): string {
    return number_format((int) $value, 0, ',', '.');
};
$fmtPct = static function ($value): string {
    return number_format((float) $value, 2, ',', '.') . '%';
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1">Product Analytics</h1>
        <p class="mb-0">Lihat kualitas aktivasi user, cohort retention, adopsi fitur, dan conversion funnel produk.</p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-chart-line me-1"></i><?= e((string) $months); ?> month window</span>
    </div>
</section>

<section class="admin-panel mb-3">
    <form method="get" action="<?= e(base_url('/admin/analytics')); ?>" class="admin-filter-grid">
        <div>
            <label class="form-label form-label-sm mb-1">Cohort Window (month)</label>
            <input type="number" min="3" max="12" name="months" class="form-control form-control-sm" value="<?= e((string) $months); ?>">
        </div>
        <div class="admin-filter-actions d-flex gap-2 align-items-end">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-arrows-rotate me-1"></i>Refresh</button>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/admin/analytics')); ?>">Reset</a>
        </div>
    </form>
</section>

<section class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-primary-subtle text-primary"><i class="fa-solid fa-calendar-day"></i></div>
            <div>
                <div class="admin-kpi-label">D1 Retention</div>
                <div class="admin-kpi-value"><?= e($fmtPct($retention['d1']['rate'] ?? 0)); ?></div>
                <small class="text-muted"><?= e($fmtInt($retention['d1']['retained'] ?? 0)); ?>/<?= e($fmtInt($retention['d1']['eligible'] ?? 0)); ?> users</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-info-subtle text-info"><i class="fa-solid fa-calendar-week"></i></div>
            <div>
                <div class="admin-kpi-label">D7 Retention</div>
                <div class="admin-kpi-value"><?= e($fmtPct($retention['d7']['rate'] ?? 0)); ?></div>
                <small class="text-muted"><?= e($fmtInt($retention['d7']['retained'] ?? 0)); ?>/<?= e($fmtInt($retention['d7']['eligible'] ?? 0)); ?> users</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-warning-subtle text-warning"><i class="fa-solid fa-calendar-days"></i></div>
            <div>
                <div class="admin-kpi-label">D30 Retention</div>
                <div class="admin-kpi-value"><?= e($fmtPct($retention['d30']['rate'] ?? 0)); ?></div>
                <small class="text-muted"><?= e($fmtInt($retention['d30']['retained'] ?? 0)); ?>/<?= e($fmtInt($retention['d30']['eligible'] ?? 0)); ?> users</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-success-subtle text-success"><i class="fa-solid fa-bolt"></i></div>
            <div>
                <div class="admin-kpi-label">Avg Days To First Tx</div>
                <div class="admin-kpi-value"><?= e((string) ($activation['avg_days_to_first_tx'] ?? 0)); ?></div>
                <small class="text-muted">Median <?= e((string) ($activation['median_days_to_first_tx'] ?? 0)); ?> hari</small>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-3">
    <div class="col-12 col-xl-7">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Cohort Retention Matrix</h5>
                <span class="badge text-bg-light">D1 / D7 / D30</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Cohort Month</th>
                            <th class="text-end">Cohort Size</th>
                            <th class="text-end">D1</th>
                            <th class="text-end">D7</th>
                            <th class="text-end">D30</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cohorts)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data cohort.</td></tr>
                        <?php else: ?>
                            <?php foreach ($cohorts as $row): ?>
                                <tr>
                                    <td><?= e((string) ($row['label'] ?? '-')); ?></td>
                                    <td class="text-end"><?= e($fmtInt($row['cohort_size'] ?? 0)); ?></td>
                                    <td class="text-end"><?= e($fmtPct($row['d1']['rate'] ?? 0)); ?></td>
                                    <td class="text-end"><?= e($fmtPct($row['d7']['rate'] ?? 0)); ?></td>
                                    <td class="text-end"><?= e($fmtPct($row['d30']['rate'] ?? 0)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Activation Insight</h5>
                <span class="badge text-bg-light">First value</span>
            </div>
            <div class="admin-health-grid">
                <div>
                    <small>Users with first transaction</small>
                    <strong><?= e($fmtInt($activation['users_with_first_tx'] ?? 0)); ?></strong>
                </div>
                <div>
                    <small>Activation 7d rate</small>
                    <strong><?= e($fmtPct($activation['activation_7d_rate'] ?? 0)); ?></strong>
                </div>
                <div>
                    <small>7d activated users</small>
                    <strong><?= e($fmtInt($activation['activation_7d_users'] ?? 0)); ?></strong>
                </div>
                <div>
                    <small>7d eligible users</small>
                    <strong><?= e($fmtInt($activation['activation_7d_eligible'] ?? 0)); ?></strong>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Feature Adoption Matrix</h5>
                <span class="badge text-bg-light"><?= e($fmtInt($adoption['total_users'] ?? 0)); ?> users</span>
            </div>

            <div class="admin-adoption-list">
                <?php foreach (($adoption['items'] ?? []) as $item): ?>
                    <?php $rate = (float) ($item['rate'] ?? 0); ?>
                    <div class="admin-adoption-item <?= empty($item['available']) ? 'is-disabled' : ''; ?>">
                        <div class="d-flex justify-content-between gap-2 align-items-center mb-1">
                            <strong><?= e((string) ($item['feature'] ?? '-')); ?></strong>
                            <span class="small text-muted"><?= e($fmtInt($item['adopted'] ?? 0)); ?>/<?= e($fmtInt($item['total'] ?? 0)); ?></span>
                        </div>
                        <div class="admin-progress-track">
                            <span class="admin-progress-fill" style="width: <?= e((string) min(100, max(0, $rate))); ?>%;"></span>
                        </div>
                        <div class="small mt-1 <?= empty($item['available']) ? 'text-warning-emphasis' : 'text-muted'; ?>">
                            <?= e($fmtPct($rate)); ?>
                            <?= empty($item['available']) ? ' (module/table belum tersedia)' : ''; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Conversion Funnel</h5>
                <span class="badge text-bg-light">Register to Active</span>
            </div>
            <div class="admin-funnel-grid">
                <?php foreach (($funnel['steps'] ?? []) as $step): ?>
                    <div class="admin-funnel-step">
                        <div class="admin-funnel-label"><?= e((string) ($step['label'] ?? '-')); ?></div>
                        <div class="admin-funnel-value"><?= e($fmtInt($step['value'] ?? 0)); ?></div>
                        <div class="admin-funnel-meta">
                            From register: <?= e($fmtPct($step['from_registered_pct'] ?? 0)); ?>
                        </div>
                        <div class="admin-funnel-meta text-muted">
                            Step conversion: <?= e($fmtPct($step['step_pct'] ?? 0)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
