<?php
$monthNames  = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$monthName   = $monthNames[$month - 1] ?? '';
$severityMap = [
    'danger'  => ['bg' => 'bg-danger-subtle',  'text' => 'text-danger',  'icon' => 'fa-circle-exclamation'],
    'warning' => ['bg' => 'bg-warning-subtle', 'text' => 'text-warning', 'icon' => 'fa-triangle-exclamation'],
    'info'    => ['bg' => 'bg-info-subtle',    'text' => 'text-info',    'icon' => 'fa-circle-info'],
];
$typeLabels  = [
    'category_spike'   => 'Lonjakan Kategori',
    'large_transaction'=> 'Transaksi Besar',
    'high_frequency'   => 'Frekuensi Tinggi',
    'new_category'     => 'Kategori Baru',
];
?>
<section class="mb-3 d-flex align-items-center justify-content-between">
    <div>
        <h4 class="mb-0"><i class="fa-solid fa-robot me-2 text-primary"></i><?= e(t('Spending Anomalies')); ?></h4>
        <small class="text-muted">Analisis cerdas pengeluaran — <?= e($monthName . ' ' . $year); ?></small>
    </div>
    <a href="<?= e(base_url('/reports')); ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-chart-pie me-1"></i><?= e(t('Reports')); ?>
    </a>
</section>

<!-- Period switcher -->
<div class="soft-card mb-3 py-2">
    <form method="get" action="<?= e(base_url('/anomalies')); ?>" class="row g-2 align-items-end">
        <div class="col-5">
            <label class="form-label small"><?= e(t('Month')); ?></label>
            <input type="number" name="month" class="form-control form-control-sm" min="1" max="12" value="<?= (int) $month; ?>">
        </div>
        <div class="col-4">
            <label class="form-label small"><?= e(t('Year')); ?></label>
            <input type="number" name="year" class="form-control form-control-sm" min="2020" value="<?= (int) $year; ?>">
        </div>
        <div class="col-3">
            <button class="btn btn-sm btn-primary w-100" type="submit">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
    </form>
</div>

<!-- Stats bar -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="summary-card summary-card--blue p-3">
            <div class="summary-card-head">
                <div class="summary-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="summary-card-copy"><span>Total Pengeluaran</span><small><?= e($monthName); ?></small></div>
            </div>
            <div class="summary-card-bodyline">
                <p class="summary-card-amount fw-bold"><?= e(currency_format((float) $stats['total'])); ?></p>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="summary-card <?= count($anomalies) ? 'summary-card--warning' : 'summary-card--cyan'; ?> p-3">
            <div class="summary-card-head">
                <div class="summary-icon"><i class="fa-solid fa-robot"></i></div>
                <div class="summary-card-copy"><span>Anomali Terdeteksi</span><small><?= (int) $stats['tx_count']; ?> transaksi</small></div>
            </div>
            <div class="summary-card-bodyline">
                <p class="summary-card-amount fw-bold"><?= count($anomalies); ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (empty($anomalies)): ?>
    <div class="soft-card text-center py-4 text-success">
        <i class="fa-solid fa-circle-check fa-2x mb-2"></i>
        <p class="mb-0 fw-semibold">Tidak ada anomali terdeteksi</p>
        <small class="text-muted">Pengeluaran bulan ini terlihat normal 🎉</small>
    </div>
<?php else: ?>
    <div class="vstack gap-2">
        <?php foreach ($anomalies as $a):
            $sc = $severityMap[$a['severity']] ?? $severityMap['warning'];
        ?>
        <div class="soft-card p-3 <?= $sc['bg']; ?> border-0">
            <div class="d-flex align-items-start gap-3">
                <div class="mt-1">
                    <i class="fa-solid <?= $sc['icon']; ?> fa-lg <?= $sc['text']; ?>"></i>
                </div>
                <div class="flex-fill">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold small"><?= e($a['title']); ?></span>
                        <span class="badge <?= $sc['text']; ?> bg-transparent border <?= str_replace('text', 'border', $sc['text']); ?> small">
                            <?= e($typeLabels[$a['type']] ?? $a['type']); ?>
                        </span>
                    </div>
                    <p class="mb-0 small text-muted"><?= e($a['detail']); ?></p>
                    <?php if ($a['amount'] > 0): ?>
                        <span class="badge bg-secondary-subtle text-secondary mt-1">
                            <?= e(currency_format((float) $a['amount'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="text-muted small text-center mt-3">
        <i class="fa-solid fa-circle-info me-1"></i>
        Analisis berbasis pola histori pengeluaran kamu. Bukan keputusan finansial profesional.
    </p>
<?php endif; ?>
