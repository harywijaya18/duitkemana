<?php
$items = $pagination['items'] ?? [];
$total = (int) ($pagination['total'] ?? 0);
$page = (int) ($pagination['page'] ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
$statusFilter = trim((string) ($filters['status'] ?? ''));
$queryFilter = trim((string) ($filters['q'] ?? ''));
$createdFromFilter = trim((string) ($filters['created_from'] ?? ''));
$createdToFilter = trim((string) ($filters['created_to'] ?? ''));
$activityFilter = trim((string) ($filters['activity'] ?? ''));

$fmtInt = static function ($value): string {
    return number_format((int) $value, 0, ',', '.');
};
$fmtMoney = static function ($value): string {
    return 'IDR ' . number_format((float) $value, 0, ',', '.');
};

$buildPageUrl = static function (int $targetPage, string $status, string $q, string $createdFrom, string $createdTo, string $activity): string {
    $params = ['page' => max(1, $targetPage)];
    if ($status !== '') {
        $params['status'] = $status;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($createdFrom !== '') {
        $params['created_from'] = $createdFrom;
    }
    if ($createdTo !== '') {
        $params['created_to'] = $createdTo;
    }
    if ($activity !== '') {
        $params['activity'] = $activity;
    }
    return base_url('/admin/users') . '?' . http_build_query($params);
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1">User Management</h1>
        <p class="mb-0">Kelola status akun user, cari user spesifik, dan pantau jejak aksi admin.</p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-users me-1"></i><?= e($fmtInt($total)); ?> users</span>
    </div>
</section>

<section class="row g-3">
    <div class="col-12">
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h5 class="mb-0">Daftar User</h5>
                <span class="badge text-bg-light">Page <?= e((string) $page); ?>/<?= e((string) $totalPages); ?></span>
            </div>

            <form method="get" action="<?= e(base_url('/admin/users')); ?>" class="admin-filter-grid mb-3">
                <div>
                    <label class="form-label form-label-sm mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Search</label>
                    <input type="text" name="q" value="<?= e($queryFilter); ?>" class="form-control form-control-sm" placeholder="name or email">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Created From</label>
                    <input type="date" name="created_from" value="<?= e($createdFromFilter); ?>" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Created To</label>
                    <input type="date" name="created_to" value="<?= e($createdToFilter); ?>" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label form-label-sm mb-1">Activity</label>
                    <select name="activity" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="inactive" <?= $activityFilter === 'inactive' ? 'selected' : ''; ?>>Inactive (0 tx)</option>
                        <option value="low" <?= $activityFilter === 'low' ? 'selected' : ''; ?>>Low (1-10 tx)</option>
                        <option value="medium" <?= $activityFilter === 'medium' ? 'selected' : ''; ?>>Medium (11-50 tx)</option>
                        <option value="high" <?= $activityFilter === 'high' ? 'selected' : ''; ?>>High (51+ tx)</option>
                    </select>
                </div>
                <div class="admin-filter-actions d-flex gap-2 align-items-end">
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fa-solid fa-filter me-1"></i>Filter
                    </button>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/admin/users')); ?>">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Status</th>
                            <th class="text-end">Transactions</th>
                            <th class="text-end">Total Expense</th>
                            <th>Last Login</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada data user.</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $u): ?>
                                <?php
                                $isCurrentAdmin = (int) ($u['id'] ?? 0) === (int) ($admin['id'] ?? 0);
                                $isProtectedAdmin = is_admin_user($u);
                                $status = (string) ($u['status'] ?? 'active');
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($u['name']); ?></div>
                                        <small class="text-muted"><?= e($u['email']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($status === 'suspended'): ?>
                                            <span class="badge text-bg-danger">Suspended</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= e($fmtInt($u['tx_count'] ?? 0)); ?></td>
                                    <td class="text-end text-danger-emphasis"><?= e($fmtMoney($u['total_expense'] ?? 0)); ?></td>
                                    <td>
                                        <?php if (!empty($u['last_login_at'])): ?>
                                            <?= e(date('d M Y H:i', strtotime((string) $u['last_login_at']))); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($isCurrentAdmin || $isProtectedAdmin): ?>
                                            <span class="badge text-bg-secondary">Protected</span>
                                        <?php elseif ($status === 'suspended'): ?>
                                            <form method="post" action="<?= e(base_url('/admin/users/activate')); ?>" class="d-inline"
                                                  onsubmit="return confirm('Aktifkan kembali user ini?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                                <input type="hidden" name="user_id" value="<?= e((string) $u['id']); ?>">
                                                <button class="btn btn-outline-success btn-sm admin-icon-btn" type="submit" title="Unsuspend user" aria-label="Unsuspend user">
                                                    <i class="fa-solid fa-unlock"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="<?= e(base_url('/admin/users/reset-password')); ?>" class="d-inline"
                                                  onsubmit="return confirm('Reset password user ini? Password sementara akan ditampilkan sekali.');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                                <input type="hidden" name="user_id" value="<?= e((string) $u['id']); ?>">
                                                <button class="btn btn-outline-primary btn-sm admin-icon-btn" type="submit" title="Reset password" aria-label="Reset password">
                                                    <i class="fa-solid fa-key"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="<?= e(base_url('/admin/users/reset-api-tokens')); ?>" class="d-inline"
                                                  onsubmit="return confirm('Revoke semua API token user ini?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                                <input type="hidden" name="user_id" value="<?= e((string) $u['id']); ?>">
                                                <button class="btn btn-outline-warning btn-sm admin-icon-btn" type="submit" title="Reset API tokens" aria-label="Reset API tokens">
                                                    <i class="fa-solid fa-plug-circle-xmark"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?= e(base_url('/admin/users/suspend')); ?>" class="d-inline"
                                                  onsubmit="return confirm('Suspend user ini? User tidak akan bisa login.');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                                <input type="hidden" name="user_id" value="<?= e((string) $u['id']); ?>">
                                                <button class="btn btn-outline-danger btn-sm admin-icon-btn" type="submit" title="Suspend user" aria-label="Suspend user">
                                                    <i class="fa-solid fa-user-lock"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="<?= e(base_url('/admin/users/reset-password')); ?>" class="d-inline"
                                                  onsubmit="return confirm('Reset password user ini? Password sementara akan ditampilkan sekali.');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                                <input type="hidden" name="user_id" value="<?= e((string) $u['id']); ?>">
                                                <button class="btn btn-outline-primary btn-sm admin-icon-btn" type="submit" title="Reset password" aria-label="Reset password">
                                                    <i class="fa-solid fa-key"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="<?= e(base_url('/admin/users/reset-api-tokens')); ?>" class="d-inline"
                                                  onsubmit="return confirm('Revoke semua API token user ini?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                                <input type="hidden" name="user_id" value="<?= e((string) $u['id']); ?>">
                                                <button class="btn btn-outline-warning btn-sm admin-icon-btn" type="submit" title="Reset API tokens" aria-label="Reset API tokens">
                                                    <i class="fa-solid fa-plug-circle-xmark"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : ''; ?>"
                       href="<?= e($buildPageUrl($page - 1, $statusFilter, $queryFilter, $createdFromFilter, $createdToFilter, $activityFilter)); ?>">Prev</a>
                    <small class="text-muted">Page <?= e((string) $page); ?> of <?= e((string) $totalPages); ?></small>
                    <a class="btn btn-sm btn-outline-secondary <?= $page >= $totalPages ? 'disabled' : ''; ?>"
                       href="<?= e($buildPageUrl($page + 1, $statusFilter, $queryFilter, $createdFromFilter, $createdToFilter, $activityFilter)); ?>">Next</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12">
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h5 class="mb-0">Recent Audit Logs</h5>
                <span class="badge text-bg-light">12 latest</span>
            </div>
            <div class="row g-2">
                <?php if (empty($auditLogs)): ?>
                    <div class="col-12 text-muted">Belum ada audit log.</div>
                <?php else: ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                        <div class="admin-log-item h-100">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold"><?= e((string) ($log['action'] ?? 'action')); ?></div>
                                    <small class="text-muted">
                                        admin: <?= e((string) ($log['admin_email'] ?? '-')); ?>
                                        <?php if (!empty($log['target_email'])): ?>
                                            • target: <?= e((string) $log['target_email']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <small class="text-muted"><?= e(date('d M H:i', strtotime((string) $log['created_at']))); ?></small>
                            </div>
                        </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
