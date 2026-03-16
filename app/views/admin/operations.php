<?php
$period = $ops['period'] ?? ['year' => (int) date('Y'), 'month' => (int) date('n')];
$recurring = $ops['recurring'] ?? [];
$activeRecurringBillsBlock = $ops['active_recurring_bills'] ?? ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 20, 'total_pages' => 1];
$activeRecurringBills = $activeRecurringBillsBlock['items'] ?? [];
$activeBillsPage = (int) ($activeRecurringBillsBlock['page'] ?? 1);
$activeBillsPerPage = (int) ($activeRecurringBillsBlock['per_page'] ?? 20);
$activeBillsTotalPages = (int) ($activeRecurringBillsBlock['total_pages'] ?? 1);
$activeBillsTotal = (int) ($activeRecurringBillsBlock['total'] ?? 0);
$mismatchesBlock = $ops['mismatches'] ?? ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 20, 'total_pages' => 1];
$mismatches = $mismatchesBlock['items'] ?? [];
$mismatchesPage = (int) ($mismatchesBlock['page'] ?? 1);
$mismatchesPerPage = (int) ($mismatchesBlock['per_page'] ?? 20);
$mismatchesTotalPages = (int) ($mismatchesBlock['total_pages'] ?? 1);
$mismatchesTotal = (int) ($mismatchesBlock['total'] ?? 0);

$duplicatesBlock = $ops['duplicates'] ?? ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 20, 'total_pages' => 1];
$duplicates = $duplicatesBlock['items'] ?? [];
$duplicatesPage = (int) ($duplicatesBlock['page'] ?? 1);
$duplicatesPerPage = (int) ($duplicatesBlock['per_page'] ?? 20);
$duplicatesTotalPages = (int) ($duplicatesBlock['total_pages'] ?? 1);
$duplicatesTotal = (int) ($duplicatesBlock['total'] ?? 0);
$jobs = $ops['jobs'] ?? [];
$apiHealth = $ops['api_health'] ?? [];

$fmtInt = static function ($value): string {
    return number_format((int) $value, 0, ',', '.');
};
$fmtFloat = static function ($value, int $decimals = 1): string {
    return number_format((float) $value, $decimals, ',', '.');
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
        <a href="#active-recurring-bills-list" class="d-block text-decoration-none text-reset" title="Lihat daftar Active Recurring Bills">
            <div class="admin-kpi-card" style="cursor:pointer;">
                <div class="admin-kpi-icon bg-primary-subtle text-primary"><i class="fa-solid fa-repeat"></i></div>
                <div>
                    <div class="admin-kpi-label">Active Recurring Bills <small class="text-muted ms-1">(Click to view list)</small></div>
                    <div class="admin-kpi-value"><?= e($fmtInt($recurring['active_bills'] ?? 0)); ?></div>
                </div>
            </div>
        </a>
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

<section class="admin-panel mb-3" id="active-recurring-bills-list">
    <div class="admin-panel-head">
        <h5 class="mb-0">Active Recurring Bills List</h5>
        <span class="badge text-bg-light">total <?= e($fmtInt($activeBillsTotal)); ?></span>
    </div>
    <div class="small text-muted mb-2">Periode <?= e(sprintf('%02d/%04d', (int) $period['month'], (int) $period['year'])); ?>.</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle admin-table mb-0">
            <thead>
                <tr>
                    <th>Bill</th>
                    <th>User</th>
                    <th>Category</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Start</th>
                    <th class="text-end">End</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activeRecurringBills)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada recurring bills aktif pada periode ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($activeRecurringBills as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold">#<?= e((string) ($row['id'] ?? 0)); ?> - <?= e((string) ($row['name'] ?? '-')); ?></div>
                            </td>
                            <td>
                                <div><?= e((string) ($row['user_name'] ?? '-')); ?></div>
                                <small class="text-muted"><?= e((string) ($row['user_email'] ?? '-')); ?></small>
                            </td>
                            <td><?= e((string) ($row['category_name'] ?? '-')); ?></td>
                            <td class="text-end"><?= e($fmtMoney($row['amount'] ?? 0)); ?></td>
                            <td class="text-end"><?= e(sprintf('%02d/%04d', (int) ($row['start_month'] ?? 0), (int) ($row['start_year'] ?? 0))); ?></td>
                            <td class="text-end">
                                <?php if (!empty($row['end_year']) && !empty($row['end_month'])): ?>
                                    <?= e(sprintf('%02d/%04d', (int) $row['end_month'], (int) $row['end_year'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">No End</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($activeBillsTotalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="small text-muted">Page <?= e((string) $activeBillsPage); ?> of <?= e((string) $activeBillsTotalPages); ?> (<?= e($fmtInt($activeBillsPerPage)); ?>/page)</span>
            <div class="btn-group btn-group-sm" role="group" aria-label="Recurring bills pagination">
                <?php
                $prevPage = max(1, $activeBillsPage - 1);
                $nextPage = min($activeBillsTotalPages, $activeBillsPage + 1);
                $baseParams = [
                    'year' => (int) $period['year'],
                    'month' => (int) $period['month'],
                    'bills_per_page' => $activeBillsPerPage,
                ];
                $prevHref = base_url('/admin/operations?' . http_build_query(array_merge($baseParams, ['bills_page' => $prevPage])) . '#active-recurring-bills-list');
                $nextHref = base_url('/admin/operations?' . http_build_query(array_merge($baseParams, ['bills_page' => $nextPage])) . '#active-recurring-bills-list');
                ?>
                <a class="btn btn-outline-secondary <?= $activeBillsPage <= 1 ? 'disabled' : ''; ?>" href="<?= e($prevHref); ?>">Prev</a>
                <a class="btn btn-outline-secondary <?= $activeBillsPage >= $activeBillsTotalPages ? 'disabled' : ''; ?>" href="<?= e($nextHref); ?>">Next</a>
            </div>
        </div>
    <?php endif; ?>
</section>

<section class="row g-3 mb-3">
    <div class="col-12 col-xl-7">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Recurring Coverage Mismatch by User</h5>
                <span class="badge text-bg-light">total <?= e($fmtInt($mismatchesTotal)); ?></span>
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
            <?php if ($mismatchesTotalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="small text-muted">Page <?= e((string) $mismatchesPage); ?> of <?= e((string) $mismatchesTotalPages); ?> (<?= e($fmtInt($mismatchesPerPage)); ?>/page)</span>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Mismatch pagination">
                        <?php
                        $mmPrevPage = max(1, $mismatchesPage - 1);
                        $mmNextPage = min($mismatchesTotalPages, $mismatchesPage + 1);
                        $mmBaseParams = [
                            'year' => (int) $period['year'],
                            'month' => (int) $period['month'],
                            'bills_page' => $activeBillsPage,
                            'bills_per_page' => $activeBillsPerPage,
                            'duplicates_page' => $duplicatesPage,
                            'ops_per_page' => $mismatchesPerPage,
                        ];
                        $mmPrevHref = base_url('/admin/operations?' . http_build_query(array_merge($mmBaseParams, ['mismatches_page' => $mmPrevPage])) . '#active-recurring-bills-list');
                        $mmNextHref = base_url('/admin/operations?' . http_build_query(array_merge($mmBaseParams, ['mismatches_page' => $mmNextPage])) . '#active-recurring-bills-list');
                        ?>
                        <a class="btn btn-outline-secondary <?= $mismatchesPage <= 1 ? 'disabled' : ''; ?>" href="<?= e($mmPrevHref); ?>">Prev</a>
                        <a class="btn btn-outline-secondary <?= $mismatchesPage >= $mismatchesTotalPages ? 'disabled' : ''; ?>" href="<?= e($mmNextHref); ?>">Next</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Duplicate Recurring Transactions</h5>
                <span class="badge text-bg-light">total <?= e($fmtInt($duplicatesTotal)); ?></span>
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
            <?php if ($duplicatesTotalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="small text-muted">Page <?= e((string) $duplicatesPage); ?> of <?= e((string) $duplicatesTotalPages); ?> (<?= e($fmtInt($duplicatesPerPage)); ?>/page)</span>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Duplicate pagination">
                        <?php
                        $dupPrevPage = max(1, $duplicatesPage - 1);
                        $dupNextPage = min($duplicatesTotalPages, $duplicatesPage + 1);
                        $dupBaseParams = [
                            'year' => (int) $period['year'],
                            'month' => (int) $period['month'],
                            'bills_page' => $activeBillsPage,
                            'bills_per_page' => $activeBillsPerPage,
                            'mismatches_page' => $mismatchesPage,
                            'ops_per_page' => $duplicatesPerPage,
                        ];
                        $dupPrevHref = base_url('/admin/operations?' . http_build_query(array_merge($dupBaseParams, ['duplicates_page' => $dupPrevPage])) . '#active-recurring-bills-list');
                        $dupNextHref = base_url('/admin/operations?' . http_build_query(array_merge($dupBaseParams, ['duplicates_page' => $dupNextPage])) . '#active-recurring-bills-list');
                        ?>
                        <a class="btn btn-outline-secondary <?= $duplicatesPage <= 1 ? 'disabled' : ''; ?>" href="<?= e($dupPrevHref); ?>">Prev</a>
                        <a class="btn btn-outline-secondary <?= $duplicatesPage >= $duplicatesTotalPages ? 'disabled' : ''; ?>" href="<?= e($dupNextHref); ?>">Next</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Job Queue Status</h5>
                <span class="badge <?= !empty($jobs['available']) ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                    <?= (($jobs['mode'] ?? '') === 'table') ? 'Configured' : (((($jobs['mode'] ?? '') === 'derived') ? 'Derived' : 'Not Configured')); ?>
                </span>
            </div>
            <?php if (!empty($jobs['available'])): ?>
                <ul class="mb-0">
                    <?php if (($jobs['mode'] ?? '') === 'table'): ?>
                        <li>Table: <strong><?= e((string) ($jobs['table'] ?? '-')); ?></strong></li>
                    <?php else: ?>
                        <li>Source: <strong>Recurring Backlog Estimation</strong></li>
                        <li>Expected recurring jobs: <strong><?= e($fmtInt($jobs['expected'] ?? 0)); ?></strong></li>
                        <li>Generated recurring jobs: <strong><?= e($fmtInt($jobs['generated'] ?? 0)); ?></strong></li>
                    <?php endif; ?>
                    <li>Pending jobs: <strong><?= e($fmtInt($jobs['pending'] ?? 0)); ?></strong></li>
                    <li>Failed jobs/anomalies: <strong><?= e($fmtInt($jobs['failed'] ?? 0)); ?></strong></li>
                    <li>Last failed at: <strong><?= e((string) ($jobs['last_failed_at'] ?? '-')); ?></strong></li>
                </ul>
                <?php if (!empty($jobs['hint'])): ?>
                    <div class="text-muted mt-2"><?= e((string) $jobs['hint']); ?></div>
                <?php endif; ?>
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
                    <?= !empty($apiHealth['available']) ? 'Live' : 'Not Available'; ?>
                </span>
            </div>
            <?php if (!empty($apiHealth['available'])): ?>
                <div class="small text-muted mb-2">Window: <?= e((string) ($apiHealth['window_label'] ?? 'Last 24h')); ?></div>
                <div class="table-responsive mb-2">
                    <table class="table table-sm align-middle admin-table mb-0">
                        <tbody>
                            <tr>
                                <td>Total requests</td>
                                <td class="text-end fw-semibold"><?= e($fmtInt($apiHealth['total_requests'] ?? 0)); ?></td>
                            </tr>
                            <tr>
                                <td>Avg latency</td>
                                <td class="text-end fw-semibold"><?= e($fmtFloat($apiHealth['avg_latency_ms'] ?? 0, 1)); ?> ms</td>
                            </tr>
                            <tr>
                                <td>P95 latency</td>
                                <td class="text-end fw-semibold"><?= e($fmtFloat($apiHealth['p95_latency_ms'] ?? 0, 1)); ?> ms</td>
                            </tr>
                            <tr>
                                <td>Server error ratio (5xx)</td>
                                <td class="text-end fw-semibold text-danger"><?= e($fmtFloat($apiHealth['error_ratio_pct'] ?? 0, 2)); ?>%</td>
                            </tr>
                            <tr>
                                <td>Client error ratio (4xx)</td>
                                <td class="text-end fw-semibold text-warning"><?= e($fmtFloat($apiHealth['client_error_ratio_pct'] ?? 0, 2)); ?>%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="small fw-semibold mb-1">Top endpoints</div>
                <div class="table-responsive mb-2">
                    <table class="table table-sm align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th>Endpoint</th>
                                <th class="text-end">Req</th>
                                <th class="text-end">Avg ms</th>
                                <th class="text-end">5xx</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($apiHealth['endpoints'])): ?>
                                <tr><td colspan="4" class="text-center text-muted py-2">Belum ada trafik endpoint.</td></tr>
                            <?php else: ?>
                                <?php foreach (($apiHealth['endpoints'] ?? []) as $row): ?>
                                    <tr>
                                        <td><span class="badge text-bg-light me-1"><?= e((string) ($row['method'] ?? 'GET')); ?></span><?= e((string) ($row['path'] ?? '-')); ?></td>
                                        <td class="text-end"><?= e($fmtInt($row['requests'] ?? 0)); ?></td>
                                        <td class="text-end"><?= e($fmtFloat($row['avg_latency_ms'] ?? 0, 1)); ?></td>
                                        <td class="text-end"><?= e($fmtInt($row['server_errors'] ?? 0)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="small fw-semibold mb-1">Recent 5xx errors</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Endpoint</th>
                                <th class="text-end">Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($apiHealth['recent_errors'])): ?>
                                <tr><td colspan="3" class="text-center text-muted py-2">Tidak ada 5xx dalam window ini.</td></tr>
                            <?php else: ?>
                                <?php foreach (($apiHealth['recent_errors'] ?? []) as $row): ?>
                                    <tr>
                                        <td><?= e((string) ($row['created_at'] ?? '-')); ?></td>
                                        <td><span class="badge text-bg-light me-1"><?= e((string) ($row['method'] ?? 'GET')); ?></span><?= e((string) ($row['path'] ?? '-')); ?></td>
                                        <td class="text-end text-danger fw-semibold"><?= e($fmtInt($row['status_code'] ?? 0)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($apiHealth['message'])): ?>
                    <div class="text-muted mt-2"><?= e((string) $apiHealth['message']); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-muted">
                    <?= e((string) ($apiHealth['message'] ?? 'Belum ada data telemetry API.')); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
