<?php
$monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$monthName  = $monthNames[$month - 1] ?? '';
$totalGoal  = array_sum(array_column($goals, 'goal_amount'));
$totalSpent = array_sum(array_column($goals, 'spent'));
?>
<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0"><?= e(t('Budget Goals')); ?></h4>
    <a href="<?= e(base_url('/budget')); ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i><?= e(t('Monthly Budget')); ?>
    </a>
</section>

<!-- Period switcher -->
<div class="soft-card mb-3 py-2">
    <form method="get" action="<?= e(base_url('/budget/goals')); ?>" class="row g-2 align-items-end">
        <div class="col-5">
            <label class="form-label small"><?= e(t('Month')); ?></label>
            <input type="number" name="month" class="form-control form-control-sm" min="1" max="12" value="<?= (int) $month; ?>">
        </div>
        <div class="col-4">
            <label class="form-label small"><?= e(t('Year')); ?></label>
            <input type="number" name="year" class="form-control form-control-sm" min="2020" value="<?= (int) $year; ?>">
        </div>
        <div class="col-3">
            <button class="btn btn-sm btn-primary w-100" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
    </form>
</div>

<!-- Summary -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="summary-card summary-card--blue p-3">
            <div class="summary-card-head">
                <div class="summary-icon"><i class="fa-solid fa-bullseye"></i></div>
                <div class="summary-card-copy">
                    <span><?= e(t('Total Goal')); ?></span>
                    <small><?= e($monthName . ' ' . $year); ?></small>
                </div>
            </div>
            <div class="summary-card-bodyline">
                <p class="summary-card-amount fw-bold"><?= e(currency_format($totalGoal)); ?></p>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="summary-card <?= $totalGoal > 0 && $totalSpent >= $totalGoal ? 'summary-card--warning' : 'summary-card--cyan'; ?> p-3">
            <div class="summary-card-head">
                <div class="summary-icon"><i class="fa-solid fa-wallet"></i></div>
                <div class="summary-card-copy">
                    <span><?= e(t('Total Spent')); ?></span>
                    <small><?= $totalGoal > 0 ? round(($totalSpent / $totalGoal) * 100) . '% of goal' : '—'; ?></small>
                </div>
            </div>
            <div class="summary-card-bodyline">
                <p class="summary-card-amount fw-bold"><?= e(currency_format($totalSpent)); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Goal list -->
<?php if (!empty($goals)): ?>
    <div class="vstack gap-2 mb-3">
        <?php foreach ($goals as $goal):
            $pct    = $goal['goal_amount'] > 0 ? min(($goal['spent'] / $goal['goal_amount']) * 100, 100) : 0;
            $over   = (float) $goal['spent'] > (float) $goal['goal_amount'];
            $barCls = $over ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
        ?>
        <div class="soft-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fw-semibold">
                    <i class="fa-solid <?= e($goal['category_icon']); ?> me-1 text-primary"></i>
                    <?= e($goal['category_name']); ?>
                </span>
                <form method="post" action="<?= e(base_url('/budget/goals/delete')); ?>"
                      onsubmit="return confirm('Hapus goal ini?')">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                    <input type="hidden" name="goal_id" value="<?= (int) $goal['id']; ?>">
                    <input type="hidden" name="month"   value="<?= (int) $month; ?>">
                    <input type="hidden" name="year"    value="<?= (int) $year; ?>">
                    <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Hapus"><i class="fa-solid fa-trash-can"></i></button>
                </form>
            </div>
            <div class="progress mb-1" style="height:8px">
                <div class="progress-bar <?= $barCls; ?>" style="width:<?= round($pct); ?>%"></div>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span><?= e(currency_format((float) $goal['spent'])); ?> dipakai</span>
                <span>Target: <?= e(currency_format((float) $goal['goal_amount'])); ?></span>
            </div>
            <?php if ($over): ?>
                <div class="badge bg-danger-subtle text-danger mt-1">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Melebihi target +<?= e(currency_format((float) $goal['spent'] - (float) $goal['goal_amount'])); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-secondary text-center mb-3">
        <i class="fa-solid fa-bullseye me-2"></i>Belum ada goal untuk periode ini.
    </div>
<?php endif; ?>

<!-- Add goal form -->
<div class="soft-card">
    <h6 class="fw-bold mb-3"><i class="fa-solid fa-plus-circle me-2 text-primary"></i><?= e(t('Add Goal')); ?></h6>
    <form method="post" action="<?= e(base_url('/budget/goals/save')); ?>" class="vstack gap-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="month"      value="<?= (int) $month; ?>">
        <input type="hidden" name="year"       value="<?= (int) $year; ?>">
        <div>
            <label class="form-label small"><?= e(t('Category')); ?></label>
            <select name="category_id" class="form-select form-select-sm" required>
                <option value="">— Pilih kategori —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id']; ?>"><?= e($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label small"><?= e(t('Goal Amount')); ?></label>
            <input type="text" inputmode="numeric" name="goal_amount"
                   class="form-control form-control-sm fmt-idr"
                   placeholder="0" required>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">
            <i class="fa-solid fa-floppy-disk me-1"></i><?= e(t('Save Goal')); ?>
        </button>
    </form>
</div>

<script>
function fmtIdr(n) { return n === '' ? '' : Number(n).toLocaleString('id-ID', {maximumFractionDigits:0}); }
function stripIdr(v) { return String(v).replace(/\./g,'').replace(/[^0-9]/g,''); }
document.querySelectorAll('.fmt-idr').forEach(el => {
    el.addEventListener('focus', () => { el.value = stripIdr(el.value); });
    el.addEventListener('blur',  () => { const n = stripIdr(el.value); el.value = n ? fmtIdr(n) : ''; });
});
document.querySelector('form[action*="save"]')?.addEventListener('submit', function() {
    this.querySelectorAll('.fmt-idr').forEach(el => { el.value = stripIdr(el.value); });
});
</script>
