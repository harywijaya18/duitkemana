<?php
$typeMap = [
    'danger'  => ['icon' => 'fa-circle-exclamation', 'cls' => 'text-danger',  'badge' => 'bg-danger'],
    'warning' => ['icon' => 'fa-triangle-exclamation','cls'=> 'text-warning', 'badge' => 'bg-warning text-dark'],
    'info'    => ['icon' => 'fa-circle-info',         'cls' => 'text-info',   'badge' => 'bg-info text-dark'],
    'success' => ['icon' => 'fa-circle-check',        'cls' => 'text-success','badge' => 'bg-success'],
];
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
?>
<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0">
        <i class="fa-solid fa-bell me-2 text-primary"></i><?= e(t('Notifications')); ?>
        <?php if ($unreadCount > 0): ?>
            <span class="badge bg-danger ms-1"><?= $unreadCount; ?></span>
        <?php endif; ?>
    </h4>
    <?php if ($unreadCount > 0): ?>
        <form method="post" action="<?= e(base_url('/notifications/read-all')); ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <button class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-check-double me-1"></i>Tandai semua dibaca
            </button>
        </form>
    <?php endif; ?>
</section>

<?php if (empty($notifications)): ?>
    <div class="soft-card text-center py-4 text-muted">
        <i class="fa-solid fa-bell-slash fa-2x mb-2 d-block"></i>
        Tidak ada notifikasi.
    </div>
<?php else: ?>
    <div class="vstack gap-2">
        <?php foreach ($notifications as $notif):
            $t_ = $typeMap[$notif['type']] ?? $typeMap['info'];
            $isUnread = !$notif['is_read'];
        ?>
        <div class="soft-card p-3 <?= $isUnread ? 'border-start border-3 border-primary' : 'opacity-75'; ?>">
            <div class="d-flex align-items-start gap-3">
                <div class="mt-1">
                    <i class="fa-solid <?= $t_['icon']; ?> <?= $t_['cls']; ?>"></i>
                </div>
                <div class="flex-fill">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold small <?= $isUnread ? '' : 'text-muted'; ?>">
                            <?= e($notif['title']); ?>
                        </span>
                        <?php if ($isUnread): ?>
                            <form method="post" action="<?= e(base_url('/notifications/read')); ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                <input type="hidden" name="id"         value="<?= (int) $notif['id']; ?>">
                                <button class="btn btn-xs py-0 px-1 btn-outline-secondary small" title="Tandai dibaca">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if ($notif['message']): ?>
                        <p class="mb-1 small text-muted"><?= e($notif['message']); ?></p>
                    <?php endif; ?>
                    <small class="text-muted">
                        <i class="fa-regular fa-clock me-1"></i>
                        <?= e(date('d M Y H:i', strtotime($notif['created_at']))); ?>
                    </small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
