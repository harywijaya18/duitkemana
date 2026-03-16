<?php
$kpis = $snapshot['kpis'] ?? [];
$recurring = $snapshot['recurring'] ?? [];
$trend = $snapshot['trend'] ?? [];
$topSpenders = $snapshot['top_spenders'] ?? [];
$recentUsers = $snapshot['recent_users'] ?? [];

$fmtInt = static function ($value): string {
    return number_format((int) $value, 0, ',', '.');
};
$fmtMoney = static function ($value): string {
    return 'IDR ' . number_format((float) $value, 0, ',', '.');
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1">Admin Dashboard</h1>
        <p class="mb-0">Pantau performa platform SaaS secara menyeluruh dalam mode desktop.</p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-calendar-days me-1"></i><?= e(date('d M Y H:i')); ?> WIB</span>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-primary-subtle text-primary"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="admin-kpi-label">Total Users</div>
                <div class="admin-kpi-value"><?= e($fmtInt($kpis['total_users'] ?? 0)); ?></div>
                <small class="text-muted">+<?= e($fmtInt($kpis['new_users_30d'] ?? 0)); ?> user baru (30 hari)</small>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-info-subtle text-info"><i class="fa-solid fa-user-check"></i></div>
            <div>
                <div class="admin-kpi-label">Active Users 30d</div>
                <div class="admin-kpi-value"><?= e($fmtInt($kpis['active_users_30d'] ?? 0)); ?></div>
                <small class="text-muted"><?= e($fmtInt($kpis['transactions_30d'] ?? 0)); ?> transaksi (30 hari)</small>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-danger-subtle text-danger"><i class="fa-solid fa-money-bill-wave"></i></div>
            <div>
                <div class="admin-kpi-label">Expense 30d</div>
                <div class="admin-kpi-value"><?= e($fmtMoney($kpis['expense_30d'] ?? 0)); ?></div>
                <small class="text-muted">Akumulasi pengeluaran user</small>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-success-subtle text-success"><i class="fa-solid fa-sack-dollar"></i></div>
            <div>
                <div class="admin-kpi-label">Income 30d</div>
                <div class="admin-kpi-value"><?= e($fmtMoney($kpis['income_30d'] ?? 0)); ?></div>
                <small class="text-muted">MRR proxy bulan ini: <?= e($fmtMoney($kpis['mrr_proxy'] ?? 0)); ?></small>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12 col-xl-7">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Trend 6 Bulan</h5>
                <span class="badge text-bg-light">Users, transaksi, income, expense</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="text-end">User Baru</th>
                            <th class="text-end">Transaksi</th>
                            <th class="text-end">Expense</th>
                            <th class="text-end">Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trend as $row): ?>
                            <tr>
                                <td><?= e($row['label']); ?></td>
                                <td class="text-end"><?= e($fmtInt($row['new_users'])); ?></td>
                                <td class="text-end"><?= e($fmtInt($row['transactions'])); ?></td>
                                <td class="text-end text-danger-emphasis"><?= e($fmtMoney($row['expense'])); ?></td>
                                <td class="text-end text-success-emphasis"><?= e($fmtMoney($row['income'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Recurring Scheduler Health</h5>
                <?php if (!empty($recurring['available'])): ?>
                    <span class="badge text-bg-success">Available</span>
                <?php else: ?>
                    <span class="badge text-bg-secondary">Unavailable</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($recurring['available'])): ?>
                <div class="admin-health-grid">
                    <div>
                        <small>Active Bills</small>
                        <strong><?= e($fmtInt($recurring['active_bills'] ?? 0)); ?></strong>
                    </div>
                    <div>
                        <small>Generated Rows</small>
                        <strong><?= e($fmtInt($recurring['generated_rows'] ?? 0)); ?></strong>
                    </div>
                    <div>
                        <small>Generated Amount</small>
                        <strong><?= e($fmtMoney($recurring['generated_amount'] ?? 0)); ?></strong>
                    </div>
                    <div>
                        <small>Coverage</small>
                        <strong><?= e((string) ($recurring['coverage_pct'] ?? 0)); ?>%</strong>
                    </div>
                </div>
                <div class="mt-3">
                    <a class="btn btn-outline-primary btn-sm" href="<?= e(base_url('/health/recurring')); ?>" target="_blank" rel="noopener">
                        <i class="fa-solid fa-heart-pulse me-1"></i>Buka JSON Health Check
                    </a>
                </div>
            <?php else: ?>
                <div class="text-muted">Tabel recurring_bills belum tersedia di database.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Top Spenders Bulan Ini</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th class="text-end">Tx</th>
                            <th class="text-end">Total Expense</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topSpenders)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data transaksi bulan ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topSpenders as $row): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($row['name']); ?></div>
                                        <small class="text-muted"><?= e($row['email']); ?></small>
                                    </td>
                                    <td class="text-end"><?= e($fmtInt($row['tx_count'])); ?></td>
                                    <td class="text-end text-danger-emphasis fw-semibold"><?= e($fmtMoney($row['total_expense'])); ?></td>
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
                <h5 class="mb-0">Recent Signups</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Currency</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($u['name']); ?></div>
                                    <small class="text-muted"><?= e($u['email']); ?></small>
                                </td>
                                <td><?= e($u['currency']); ?></td>
                                <td><?= e(date('d M Y', strtotime($u['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
