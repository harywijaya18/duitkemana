<?php
$supportTickets = $supportTickets ?? ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 10, 'total_pages' => 1];
$supportEnabled = $supportEnabled ?? false;
$ticketItems = $supportTickets['items'] ?? [];
$ticketPage = (int) ($supportTickets['page'] ?? 1);
$ticketTotalPages = (int) ($supportTickets['total_pages'] ?? 1);
$ticketBuildUrl = static function (int $targetPage): string {
    return base_url('/profile/support-center?' . http_build_query(['ticket_page' => max(1, $targetPage)]));
};
?>

<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0"><?= e(t('Support Center')); ?></h4>
    <a href="<?= e(base_url('/profile')); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i><?= e(t('Profile')); ?>
    </a>
</section>

<div class="soft-card support-hero-card mb-3">
    <div class="support-hero-icon"><i class="fa-solid fa-headset"></i></div>
    <div>
        <div class="profile-section-label"><?= e(t('Support & Help')); ?></div>
        <div class="text-muted small"><?= e(t('Need help? Send a ticket and track the latest status here.')); ?></div>
    </div>
</div>

<div class="soft-card profile-section-card mb-3">
    <?php if (!$supportEnabled): ?>
        <div class="text-muted"><?= e(t('Support Center is currently unavailable.')); ?></div>
    <?php else: ?>
        <div class="profile-section-head">
            <div>
                <div class="profile-section-label"><?= e(t('Send Ticket')); ?></div>
                <div class="text-muted small"><?= e(t('Describe your issue so the admin team can follow it up faster.')); ?></div>
            </div>
        </div>

        <form method="post" action="<?= e(base_url('/profile/support-ticket')); ?>" class="vstack gap-3 mb-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <div class="row g-3">
                <div class="col-6">
                <label class="form-label"><?= e(t('Category')); ?></label>
                <select name="category" class="form-select">
                    <option value="general"><?= e(t('General')); ?></option>
                    <option value="billing"><?= e(t('Billing')); ?></option>
                    <option value="technical"><?= e(t('Technical')); ?></option>
                    <option value="feature_request"><?= e(t('Feature Request')); ?></option>
                    <option value="account"><?= e(t('Account')); ?></option>
                </select>
                </div>
                <div class="col-6">
                <label class="form-label"><?= e(t('Priority')); ?></label>
                <select name="priority" class="form-select">
                    <option value="low"><?= e(t('Low')); ?></option>
                    <option value="normal" selected><?= e(t('Normal')); ?></option>
                    <option value="high"><?= e(t('High')); ?></option>
                    <option value="urgent"><?= e(t('Urgent')); ?></option>
                </select>
                </div>
            </div>
            <div>
                <label class="form-label"><?= e(t('Subject')); ?></label>
                <input type="text" name="subject" class="form-control" maxlength="180" required>
            </div>
            <div>
                <label class="form-label"><?= e(t('Message')); ?></label>
                <textarea name="initial_message" class="form-control" rows="5" required></textarea>
            </div>
            <button class="btn btn-primary" type="submit">
                <i class="fa-solid fa-paper-plane me-2"></i><?= e(t('Send Ticket')); ?>
            </button>
        </form>

        <div class="profile-section-head">
            <div>
                <div class="profile-section-label"><?= e(t('My Support Tickets')); ?></div>
                <div class="text-muted small">Page <?= e((string) $ticketPage); ?>/<?= e((string) $ticketTotalPages); ?></div>
            </div>
        </div>

        <?php if (empty($ticketItems)): ?>
            <div class="text-muted"><?= e(t('No support tickets yet.')); ?></div>
        <?php else: ?>
            <div class="support-ticket-stack">
                <?php foreach ($ticketItems as $ticket): ?>
                    <div class="support-ticket-item">
                        <div class="d-flex justify-content-between gap-3 align-items-start mb-2">
                            <div>
                                <div class="fw-semibold"><?= e((string) ($ticket['subject'] ?? '-')); ?></div>
                                <div class="text-muted small mt-1"><?= e((string) ($ticket['initial_message'] ?? '')); ?></div>
                            </div>
                            <span class="support-ticket-status"><?= e((string) ($ticket['status'] ?? '-')); ?></span>
                        </div>
                        <div class="support-ticket-meta">
                            <span><?= e(t('Category')); ?>: <?= e((string) ($ticket['category'] ?? '-')); ?></span>
                            <span><?= e(t('Priority')); ?>: <?= e((string) ($ticket['priority'] ?? '-')); ?></span>
                            <span><?= e(t('Last Message')); ?>: <?= e((string) ($ticket['last_message_at'] ?? '-')); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($ticketTotalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <a class="btn btn-sm btn-outline-secondary <?= $ticketPage <= 1 ? 'disabled' : ''; ?>" href="<?= e($ticketBuildUrl($ticketPage - 1)); ?>">Prev</a>
                    <small class="text-muted">Page <?= e((string) $ticketPage); ?> of <?= e((string) $ticketTotalPages); ?></small>
                    <a class="btn btn-sm btn-outline-secondary <?= $ticketPage >= $ticketTotalPages ? 'disabled' : ''; ?>" href="<?= e($ticketBuildUrl($ticketPage + 1)); ?>">Next</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
