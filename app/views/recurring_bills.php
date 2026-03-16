<?php
$monthNames = [
    1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
    7=>'Jul',8=>'Ags',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des',
];
$monthNamesEn = [
    1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
    7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec',
];
$isEn   = current_language() === 'en';
$mShort = $isEn ? $monthNamesEn : $monthNames;

$totalThisMonth = array_sum(array_column($thisMonth, 'amount'));
?>

<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0">
        <i class="fa-solid fa-rotate me-1 text-primary"></i>
        <?= e(t('Recurring Bills')); ?>
    </h4>
    <a href="<?= e(base_url('/bills/add')); ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i><?= e(t('Add Bill')); ?>
    </a>
</section>

<!-- ─── This month summary + generate button ─── -->
<div class="soft-card mb-3" style="border-left:3px solid var(--success);background:linear-gradient(135deg,#f0fdf4,#dcfce7)">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="fw-bold" style="color:var(--success);font-size:14px">
            <i class="fa-solid fa-calendar-check me-1"></i>
            <?= e(t('Bills This Month')); ?>
        </div>
        <form method="post" action="<?= e(base_url('/bills/generate')); ?>" class="d-flex align-items-center gap-2"
              onsubmit="return confirm('<?= e(t('Generate recurring bills as transactions?')); ?>')">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <select name="month" class="form-select form-select-sm" style="width:auto;min-width:80px">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m; ?>" <?= $m === $curMonth ? 'selected' : ''; ?>>
                        <?= e($mShort[$m] ?? (string) $m); ?>
                    </option>
                <?php endfor; ?>
            </select>
            <input type="number" name="year" class="form-control form-control-sm" style="width:88px" min="2000" max="2099" value="<?= $curYear; ?>" required>
            <button type="submit" class="btn btn-sm"
                    style="background:#dcfce7;color:#15803d;border:1px solid #86efac">
                <i class="fa-solid fa-bolt me-1"></i><?= e(t('Generate Now')); ?>
            </button>
        </form>
    </div>

    <?php if (empty($thisMonth)): ?>
        <div class="text-muted" style="font-size:13px"><?= e(t('No active bills this month.')); ?></div>
    <?php else: ?>
        <div class="vstack gap-1 mb-2">
            <?php foreach ($thisMonth as $b): ?>
                <div class="d-flex justify-content-between align-items-center" style="font-size:13px">
                    <span>
                        <?= e($b['name']); ?>
                        <?php if ($b['category_name']): ?>
                            <span class="text-muted" style="font-size:11px">(<?= e($b['category_name']); ?>)</span>
                        <?php endif; ?>
                    </span>
                    <span class="fw-bold" style="color:var(--danger)"><?= e(currency_format((float)$b['amount'])); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-between fw-bold border-top pt-2" style="font-size:14px">
            <span><?= e(t('Total')); ?></span>
            <span style="color:var(--danger)"><?= e(currency_format($totalThisMonth)); ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- ─── All bills list ─── -->
<?php if (empty($bills)): ?>
    <div class="soft-card text-center text-muted py-4">
        <i class="fa-solid fa-rotate mb-2 d-block" style="font-size:28px;opacity:.3"></i>
        <div><?= e(t('No recurring bills yet.')); ?></div>
        <a href="<?= e(base_url('/bills/add')); ?>" class="btn btn-primary btn-sm mt-3">
            <?= e(t('Add First Bill')); ?>
        </a>
    </div>
<?php else: ?>
    <div class="vstack gap-2">
        <?php foreach ($bills as $bill): ?>
            <?php
            $isActive   = (bool) $bill['is_active'];
            $startLabel = ($mShort[(int)$bill['start_month']] ?? '') . ' ' . $bill['start_year'];

            if ($bill['end_year'] && $bill['end_month']) {
                $endLabel = ($mShort[(int)$bill['end_month']] ?? '') . ' ' . $bill['end_year'];
                $monthsLeft = null;
                if ($isActive) {
                    $remaining = ((int)$bill['end_year'] - $curYear) * 12
                               + ((int)$bill['end_month'] - $curMonth);
                    if ($remaining > 0) {
                        $monthsLeft = $remaining . ' ' . t('months left');
                    } elseif ($remaining === 0) {
                        $monthsLeft = t('Last month');
                    }
                }
            } else {
                $endLabel   = '∞';
                $monthsLeft = null;
            }
            ?>
            <div class="soft-card" style="border-left:3px solid <?= $isActive ? 'var(--primary)' : '#cbd5e1'; ?>">
                <div class="d-flex align-items-start justify-content-between">
                    <div style="flex:1;min-width:0">
                        <div class="fw-bold text-truncate"><?= e($bill['name']); ?></div>
                        <div style="font-size:12px" class="text-muted mt-1">
                            <?php if ($bill['category_name']): ?>
                                <span class="badge bg-light text-dark border me-1"
                                      style="font-size:10px"><?= e($bill['category_name']); ?></span>
                            <?php endif; ?>
                            <?= e($startLabel); ?> → <?= e($endLabel); ?>
                            <?php if ($bill['duration_months']): ?>
                                &bull; <?= (int)$bill['duration_months']; ?> <?= e(t('months')); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($monthsLeft): ?>
                            <span class="badge mt-1"
                                  style="background:#fef9c3;color:#854d0e;font-size:11px">
                                <i class="fa-solid fa-hourglass-half me-1"></i><?= e($monthsLeft); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($bill['notes']): ?>
                            <div style="font-size:11px;color:var(--muted);margin-top:2px"><?= e($bill['notes']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end ms-2 flex-shrink-0">
                        <div class="fw-bold" style="color:<?= $isActive ? 'var(--danger)' : 'var(--muted)'; ?>">
                            <?= e(currency_format((float)$bill['amount'])); ?>
                        </div>
                        <div style="font-size:11px" class="text-muted">/<?= e(t('month')); ?></div>
                        <?php if ($isActive): ?>
                            <span class="badge bg-success mt-1" style="font-size:10px"><?= e(t('Active')); ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary mt-1" style="font-size:10px"><?= e(t('Completed')); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2 pt-2 border-top">
                    <a href="<?= e(base_url('/bills/edit?id=' . (int)$bill['id'])); ?>"
                       class="btn btn-sm btn-outline-primary flex-fill">
                        <i class="fa-solid fa-pen-to-square me-1"></i><?= e(t('Edit')); ?>
                    </a>
                    <form method="post" action="<?= e(base_url('/bills/delete')); ?>"
                          onsubmit="return confirm('<?= e(t('Delete this bill?')); ?>')"
                          style="flex:1">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?= (int)$bill['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                            <i class="fa-solid fa-trash me-1"></i><?= e(t('Delete')); ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
