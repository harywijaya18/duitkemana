<?php
$period = $ops['period'] ?? ['year' => (int) date('Y'), 'month' => (int) date('n')];
$recurring = $ops['recurring'] ?? [];
$mismatches = $ops['mismatches'] ?? [];
$duplicates = $ops['duplicates'] ?? [];
$jobs = $ops['jobs'] ?? [];
$apiHealth = $ops['api_health'] ?? [];

$fmtInt = static function ($value): string {
    return number_format((int) $value, 0, ',', '.');
};
$fmtMoney = static function ($value): string {
    return 'IDR ' . number_format((float) $value, 0, ',', '.');
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1">Operations Monitor</h1>
        <p class="mb-0">Pantau recurring generation, data consistency, dan kesiapan infrastruktur operasional.</p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-calendar-days me-1"></i><?= e(sprintf('%02d/%04d', (int) $period['month'], (int) $period['year'])); ?></span>
    </div>
</section>

<section class="admin-panel mb-3">
    <form method="get" action="<?= e(base_url('/admin/operations')); ?>" class="admin-filter-grid">
        <div>
            <label class="form-label form-label-sm mb-1">Month</label>
            <input type="number" min="1" max="12" name="month" class="form-control form-control-sm" value="<?= e((string) ((int) $period['month'])); ?>">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Year</label>
            <input type="number" min="2000" max="2099" name="year" class="form-control form-control-sm" value="<?= e((string) ((int) $period['year'])); ?>">
        </div>
        <div class="admin-filter-actions d-flex gap-2 align-items-end">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i>Apply</button>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/admin/operations')); ?>">Current</a>
        </div>
    </form>
</section>

<section class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-primary-subtle text-primary"><i class="fa-solid fa-repeat"></i></div>
            <div>
                <div class="admin-kpi-label">Active Recurring Bills</div>
                <div class="admin-kpi-value"><?= e($fmtInt($recurring['active_bills'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-success-subtle text-success"><i class="fa-solid fa-file-circle-check"></i></div>
            <div>
                <div class="admin-kpi-label">Generated Transactions</div>
                <div class="admin-kpi-value"><?= e($fmtInt($recurring['generated_rows'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-warning-subtle text-warning"><i class="fa-solid fa-percent"></i></div>
            <div>
                <div class="admin-kpi-label">Coverage</div>
                <div class="admin-kpi-value"><?= e((string) ($recurring['coverage_pct'] ?? 0)); ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-danger-subtle text-danger"><i class="fa-solid fa-money-bill-transfer"></i></div>
            <div>
                <div class="admin-kpi-label">Generated Amount</div>
                <div class="admin-kpi-value" style="font-size:18px;">
                    <?= e($fmtMoney($recurring['generated_amount'] ?? 0)); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-3">
    <div class="col-12 col-xl-7">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Recurring Coverage Mismatch by User</h5>
                <span class="badge text-bg-light">max 20</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th class="text-end">Active Bills</th>
                            <th class="text-end">Generated</th>
                            <th class="text-end">Missing</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mismatches)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada mismatch untuk periode ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($mismatches as $row): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e((string) ($row['name'] ?? '-')); ?></div>
                                        <small class="text-muted"><?= e((string) ($row['email'] ?? '-')); ?></small>
                                    </td>
                                    <td class="text-end"><?= e($fmtInt($row['active_count'] ?? 0)); ?></td>
                                    <td class="text-end"><?= e($fmtInt($row['generated_count'] ?? 0)); ?></td>
                                    <td class="text-end">
                                        <span class="badge <?= ((int) ($row['missing_count'] ?? 0)) > 0 ? 'text-bg-danger' : 'text-bg-warning'; ?>">
                                            <?= e($fmtInt($row['missing_count'] ?? 0)); ?>
                                        </span>
                                    </td>
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
                <h5 class="mb-0">Duplicate Recurring Transactions</h5>
                <span class="badge text-bg-light">max 20</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Bill ID</th>
                            <th class="text-end">Rows</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($duplicates)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Tidak ada duplikasi transaksi recurring.</td></tr>
                        <?php else: ?>
                            <?php foreach ($duplicates as $row): ?>
                                <tr>
                                    <td><?= e((string) ($row['email'] ?? '-')); ?></td>
                                    <td>#<?= e((string) ($row['recurring_bill_id'] ?? 0)); ?></td>
                                    <td class="text-end"><span class="badge text-bg-danger"><?= e($fmtInt($row['duplicate_rows'] ?? 0)); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Job Queue Status</h5>
                <span class="badge <?= !empty($jobs['available']) ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                    <?= !empty($jobs['available']) ? 'Configured' : 'Not Configured'; ?>
                </span>
            </div>
            <?php if (!empty($jobs['available'])): ?>
                <ul class="mb-0">
                    <li>Table: <strong><?= e((string) ($jobs['table'] ?? '-')); ?></strong></li>
                    <li>Pending jobs: <strong><?= e($fmtInt($jobs['pending'] ?? 0)); ?></strong></li>
                    <li>Failed jobs: <strong><?= e($fmtInt($jobs['failed'] ?? 0)); ?></strong></li>
                    <li>Last failed at: <strong><?= e((string) ($jobs['last_failed_at'] ?? '-')); ?></strong></li>
                </ul>
            <?php else: ?>
                <div class="text-muted">Tabel queue belum tersedia. Modul ini siap dihubungkan setelah job runner diterapkan.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">API Health Panel</h5>
                <span class="badge <?= !empty($apiHealth['available']) ? 'text-bg-success' : 'text-bg-warning'; ?>">
                    <?= !empty($apiHealth['available']) ? 'Live' : 'Pending Instrumentation'; ?>
                </span>
            </div>
            <div class="text-muted">
                <?= e((string) ($apiHealth['message'] ?? 'Belum ada data telemetry API.')); ?>
            </div>
        </div>
    </div>
</section>
