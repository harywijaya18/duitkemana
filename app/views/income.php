<?php
$monthNames = [
    1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
    7=>'Jul',8=>'Ags',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des',
];
$monthNamesEn = [
    1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
    7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec',
];
$isEn  = current_language() === 'en';
$mShort = $isEn ? $monthNamesEn : $monthNames;
?>

<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0"><?= e(t('Income')); ?></h4>
    <a href="<?= e(base_url('/income/add')); ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i><?= e(t('Add Income')); ?>
    </a>
</section>

<?php if (empty($records)): ?>
    <div class="soft-card text-center text-muted py-4">
        <i class="fa-solid fa-money-bill-wave mb-2 d-block" style="font-size:28px;opacity:.3"></i>
        <div><?= e(t('No income records yet.')); ?></div>
        <a href="<?= e(base_url('/income/add')); ?>" class="btn btn-primary btn-sm mt-3">
            <?= e(t('Add First Income')); ?>
        </a>
    </div>
<?php else: ?>
    <div class="vstack gap-2">
        <?php foreach ($records as $rec): ?>
            <div class="soft-card">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <strong><?= e($rec['source_name']); ?></strong>
                        <div style="font-size:12px" class="text-muted">
                            <?= e($mShort[(int)$rec['period_month']] . ' ' . $rec['period_year']); ?>
                            <?php if ($rec['working_days'] > 0): ?>
                                &bull; <?= (int)$rec['working_days']; ?> <?= e(t('work days')); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <?php
                        $hasDed  = isset($rec['total_deductions']) && (float)$rec['total_deductions'] > 0;
                        $netAmt  = (float)$rec['total_income'] - (float)($rec['total_deductions'] ?? 0);
                        ?>
                        <?php if ($hasDed): ?>
                            <div style="font-size:11px;color:var(--muted)"><?= $isEn ? 'Gross' : 'Kotor'; ?>: <?= e(currency_format((float)$rec['total_income'])); ?></div>
                            <div class="fw-700" style="color:var(--primary)"><?= e(currency_format($netAmt)); ?></div>
                            <div style="font-size:11px;color:#dc2626">-<?= e(currency_format((float)$rec['total_deductions'])); ?></div>
                        <?php else: ?>
                            <div class="fw-700" style="color:var(--success)"><?= e(currency_format((float)$rec['total_income'])); ?></div>
                        <?php endif; ?>
                        <?php if ($rec['received_date']): ?>
                            <div style="font-size:11px" class="text-muted"><?= e($rec['received_date']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Component breakdown -->
                <div class="row g-1 mt-1" style="font-size:11px;color:var(--muted)">
                    <?php if ($rec['base_salary'] > 0): ?>
                        <div class="col-6"><?= e(t('Base Salary')); ?>: <span class="fw-600 text-dark"><?= e(currency_format((float)$rec['base_salary'])); ?></span></div>
                    <?php endif; ?>
                    <?php if ($rec['meal_allowance'] > 0): ?>
                        <div class="col-6"><?= e(t('Meal Allow.')); ?>: <span class="fw-600 text-dark"><?= e(currency_format((float)$rec['meal_allowance'])); ?></span></div>
                    <?php endif; ?>
                    <?php if ($rec['transport_allowance'] > 0): ?>
                        <div class="col-6"><?= e(t('Transport Allow.')); ?>: <span class="fw-600 text-dark"><?= e(currency_format((float)$rec['transport_allowance'])); ?></span></div>
                    <?php endif; ?>
                    <?php if ($rec['position_allowance'] > 0): ?>
                        <div class="col-6"><?= e(t('Position Allow.')); ?>: <span class="fw-600 text-dark"><?= e(currency_format((float)$rec['position_allowance'])); ?></span></div>
                    <?php endif; ?>
                    <?php if ($rec['other_income'] > 0): ?>
                        <div class="col-6"><?= e(t('Other Income')); ?>: <span class="fw-600 text-dark"><?= e(currency_format((float)$rec['other_income'])); ?></span></div>
                    <?php endif; ?>
                </div>

                <?php if ($rec['notes']): ?>
                    <div class="mt-1" style="font-size:11px;color:var(--muted)"><?= e($rec['notes']); ?></div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="d-flex gap-2 mt-2">
                    <a href="<?= e(base_url('/income/edit?id=' . (int)$rec['id'])); ?>" class="btn btn-sm btn-light flex-fill">
                        <i class="fa-solid fa-pencil me-1"></i><?= e(t('Edit')); ?>
                    </a>
                    <form method="post" action="<?= e(base_url('/income/delete')); ?>"
                          onsubmit="return confirm('<?= e(t('Delete this income?')); ?>')" class="flex-fill">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?= (int)$rec['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                            <i class="fa-solid fa-trash me-1"></i><?= e(t('Delete')); ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
