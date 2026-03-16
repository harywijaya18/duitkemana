<?php
$tickets = $snapshot['tickets'] ?? ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
$summary = $snapshot['summary'] ?? [];
$drafts = $snapshot['drafts'] ?? [];
$feedback = $snapshot['feedback'] ?? [];

$statusFilter = trim((string) ($filters['status'] ?? ''));
$priorityFilter = trim((string) ($filters['priority'] ?? ''));
$queryFilter = trim((string) ($filters['q'] ?? ''));

$fmtInt = static function ($value): string {
    return number_format((int) $value, 0, ',', '.');
};

$buildUrl = static function (string $basePath, array $extra = []) use ($statusFilter, $priorityFilter, $queryFilter): string {
    $params = [];
    if ($statusFilter !== '') {
        $params['status'] = $statusFilter;
    }
    if ($priorityFilter !== '') {
        $params['priority'] = $priorityFilter;
    }
    if ($queryFilter !== '') {
        $params['q'] = $queryFilter;
    }
    foreach ($extra as $key => $value) {
        $params[$key] = $value;
    }

    if (empty($params)) {
        return base_url($basePath);
    }

    return base_url($basePath) . '?' . http_build_query($params);
};
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1">Support Center</h1>
        <p class="mb-0">Kelola tiket user, pantau prioritas penanganan, dan draft pengumuman.</p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-headset me-1"></i><?= e($fmtInt($tickets['total'] ?? 0)); ?> tickets</span>
    </div>
</section>

<?php if (!$supportAvailable): ?>
    <section class="admin-panel mb-3">
        <div class="alert alert-warning mb-0">
            Tabel support belum tersedia. Jalankan migrasi <strong>database/migrate_support_settings.sql</strong>.
        </div>
    </section>
<?php endif; ?>

<section class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-primary-subtle text-primary"><i class="fa-solid fa-inbox"></i></div>
            <div>
                <div class="admin-kpi-label">Open</div>
                <div class="admin-kpi-value"><?= e($fmtInt($summary['open'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-info-subtle text-info"><i class="fa-solid fa-spinner"></i></div>
            <div>
                <div class="admin-kpi-label">In Progress</div>
                <div class="admin-kpi-value"><?= e($fmtInt($summary['in_progress'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-success-subtle text-success"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="admin-kpi-label">Resolved</div>
                <div class="admin-kpi-value"><?= e($fmtInt($summary['resolved'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="admin-kpi-icon bg-danger-subtle text-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <div class="admin-kpi-label">High Priority</div>
                <div class="admin-kpi-value"><?= e($fmtInt($summary['high_priority'] ?? 0)); ?></div>
            </div>
        </div>
    </div>
</section>

<section class="admin-panel mb-3">
    <div class="admin-panel-head">
        <h5 class="mb-0">Ticket Inbox</h5>
        <span class="badge text-bg-light">Page <?= e((string) ((int) ($tickets['page'] ?? 1))); ?>/<?= e((string) ((int) ($tickets['total_pages'] ?? 1))); ?></span>
    </div>

    <form method="get" action="<?= e(base_url('/admin/support')); ?>" class="admin-filter-grid mb-3">
        <div>
            <label class="form-label form-label-sm mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="open" <?= $statusFilter === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Priority</label>
            <select name="priority" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="low" <?= $priorityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                <option value="normal" <?= $priorityFilter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                <option value="high" <?= $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                <option value="urgent" <?= $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
            </select>
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= e($queryFilter); ?>" placeholder="subject, category, email">
        </div>
        <div class="admin-filter-actions d-flex gap-2 align-items-end">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/admin/support')); ?>">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle admin-table mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Last Message</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets['items'])): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada tiket.</td></tr>
                <?php else: ?>
                    <?php foreach ($tickets['items'] as $row): ?>
                        <?php
                        $status = (string) ($row['status'] ?? 'open');
                        $statusClass = 'text-bg-secondary';
                        if ($status === 'open') {
                            $statusClass = 'text-bg-primary';
                        } elseif ($status === 'in_progress') {
                            $statusClass = 'text-bg-info';
                        } elseif ($status === 'resolved') {
                            $statusClass = 'text-bg-success';
                        } elseif ($status === 'closed') {
                            $statusClass = 'text-bg-dark';
                        }

                        $priority = (string) ($row['priority'] ?? 'normal');
                        $priorityClass = 'text-bg-light';
                        if ($priority === 'high') {
                            $priorityClass = 'text-bg-warning';
                        } elseif ($priority === 'urgent') {
                            $priorityClass = 'text-bg-danger';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($row['user_name'] ?? 'Guest')); ?></div>
                                <small class="text-muted"><?= e((string) ($row['user_email'] ?? '-')); ?></small>
                            </td>
                            <td><?= e((string) ($row['subject'] ?? '-')); ?></td>
                            <td><?= e((string) ($row['category'] ?? '-')); ?></td>
                            <td><span class="badge <?= e($statusClass); ?>"><?= e($status); ?></span></td>
                            <td><span class="badge <?= e($priorityClass); ?>"><?= e($priority); ?></span></td>
                            <td><?= e((string) ($row['last_message_at'] ?? '-')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ((int) ($tickets['total_pages'] ?? 1) > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <?php $page = (int) ($tickets['page'] ?? 1); ?>
            <?php $totalPages = (int) ($tickets['total_pages'] ?? 1); ?>
            <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : ''; ?>" href="<?= e($buildUrl('/admin/support', ['page' => $page - 1])); ?>">Prev</a>
            <small class="text-muted">Page <?= e((string) $page); ?> of <?= e((string) $totalPages); ?></small>
            <a class="btn btn-sm btn-outline-secondary <?= $page >= $totalPages ? 'disabled' : ''; ?>" href="<?= e($buildUrl('/admin/support', ['page' => $page + 1])); ?>">Next</a>
        </div>
    <?php endif; ?>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Feedback Categories</h5>
                <span class="badge text-bg-light">Top</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feedback)): ?>
                            <tr><td colspan="2" class="text-center text-muted py-3">Belum ada data feedback.</td></tr>
                        <?php else: ?>
                            <?php foreach ($feedback as $row): ?>
                                <tr>
                                    <td><?= e((string) ($row['category'] ?? '-')); ?></td>
                                    <td class="text-end"><?= e($fmtInt($row['total'] ?? 0)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="admin-panel h-100">
            <div class="admin-panel-head">
                <h5 class="mb-0">Announcement Drafts</h5>
                <span class="badge text-bg-light">Latest</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Audience</th>
                            <th>Status</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($drafts)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada draft pengumuman.</td></tr>
                        <?php else: ?>
                            <?php foreach ($drafts as $row): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e((string) ($row['title'] ?? '-')); ?></div>
                                        <small class="text-muted">by <?= e((string) ($row['author_email'] ?? 'system')); ?></small>
                                    </td>
                                    <td><?= e((string) ($row['audience'] ?? '-')); ?></td>
                                    <td><?= e((string) ($row['status'] ?? '-')); ?></td>
                                    <td><?= e((string) ($row['updated_at'] ?? '-')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
