<?php
$monthNames = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
];
$monthNamesEn = [
    1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December',
];
$isEn   = current_language() === 'en';
$mNames = $isEn ? $monthNamesEn : $monthNames;

// Merge historical and projection series for chart
// Build label array  →  last up-to-6 real months + 6 projection months
$chartLabels  = [];
$chartIncome  = [];
$chartExpense = [];

$histExpenseMap = [];
foreach ($histExpense as $row) {
    $histExpenseMap[$row['year'] . '-' . $row['month']] = (float)$row['total'];
}
$histIncomeMap  = [];
foreach ($histIncome as $row) {
    $histIncomeMap[$row['year'] . '-' . $row['month']] = (float)$row['total'];
}

// All months to show (historical unique months)
$allHistKeys = array_unique(array_merge(array_keys($histExpenseMap), array_keys($histIncomeMap)));
usort($allHistKeys, function($a,$b){ return strcmp($a,$b); });

foreach ($allHistKeys as $key) {
    [$y, $m] = explode('-', $key);
    $chartLabels[]  = ($mNames[(int)$m] ?? $m) . ' ' . $y;
    $chartIncome[]  = $histIncomeMap[$key]  ?? 0;
    $chartExpense[] = $histExpenseMap[$key] ?? 0;
}

// Projection months
foreach ($projections as $p) {
    $chartLabels[]  = ($mNames[$p['month']] ?? $p['month']) . ' ' . $p['year'];
    $chartIncome[]  = round($p['est_income'],  0);
    $chartExpense[] = round($p['est_expense'], 0);
}

$avgSavings  = $avgIncome - $avgExpense;
$savingsRate = $avgIncome > 0 ? round($avgSavings / $avgIncome * 100, 1) : 0;
?>

<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0"><?= e(t('Financial Projection')); ?></h4>
    <a href="<?= e(base_url('/income')); ?>" class="btn btn-sm btn-outline-primary">
        <i class="fa-solid fa-list me-1"></i><?= e(t('Income Records')); ?>
    </a>
</section>

<!-- ─── Summary cards ─── -->
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="summary-card summary-card--cyan text-center">
            <div class="summary-icon mx-auto"><i class="fa-solid fa-arrow-down-to-line"></i></div>
            <span><?= e(t('Avg Income')); ?></span>
            <h6><?= e(currency_format($avgIncome)); ?></h6>
        </div>
    </div>
    <div class="col-4">
        <div class="summary-card summary-card--blue text-center">
            <div class="summary-icon mx-auto"><i class="fa-solid fa-cart-shopping"></i></div>
            <span><?= e(t('Avg Expense')); ?></span>
            <h6><?= e(currency_format($avgExpense)); ?></h6>
        </div>
    </div>
    <div class="col-4">
        <div class="summary-card <?= $savingsRate >= 0 ? 'summary-card--purple' : 'summary-warning'; ?> text-center">
            <div class="summary-icon mx-auto"><i class="fa-solid fa-piggy-bank"></i></div>
            <span><?= e(t('Avg Savings')); ?></span>
            <h6><?= $savingsRate >= 0
                ? e(currency_format($avgSavings))
                : '<span class="text-danger">' . e(currency_format($avgSavings)) . '</span>'; ?></h6>
        </div>
    </div>
</div>

<?php if (!$salaryConfig): ?>
    <div class="insight-item soft-card mb-3">
        <i class="fa-solid fa-circle-info"></i>
        <span style="font-size:13px">
            <?= e(t('No active salary config found.')); ?>
            <a href="<?= e(base_url('/salary-config')); ?>"><?= e(t('Setup salary config')); ?></a>
            <?= e(t('to get more accurate income estimates.')); ?>
        </span>
    </div>
<?php else: ?>
    <div class="insight-item soft-card mb-3">
        <i class="fa-solid fa-building"></i>
        <span style="font-size:13px">
            <?= e(t('Using salary config')); ?>: <strong><?= e($salaryConfig['name']); ?></strong>.
            <?= e(t('Cutoff')); ?>:
            <?= $salaryConfig['cutoff_day'] == 0
                ? e(t('End of month'))
                : e(t('Day :n', ['n' => (int)$salaryConfig['cutoff_day']])); ?>.
            <?= (int)$salaryConfig['working_days_per_week']; ?> <?= e(t('days/week')); ?>.
        </span>
    </div>
<?php endif; ?>

<!-- ─── Chart ─── -->
<div class="soft-card mb-3 p-2">
    <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:6px;padding:0 6px">
        <?= e(t('Income vs Expense')); ?>
        <span style="color:#94a3b8;font-weight:400"> — <?= e(t('dashed = projection')); ?></span>
    </div>
    <canvas id="projChart" style="max-height:200px"></canvas>
</div>

<!-- ─── Projection table ─── -->
<div class="soft-card p-0 overflow-hidden mb-3">
    <div class="px-3 py-2 d-flex align-items-center justify-content-between"
         style="background:linear-gradient(135deg,#4338ca,#6d28d9);color:#fff;border-radius:var(--radius) var(--radius) 0 0">
        <span class="fw-bold" style="font-size:14px"><?= e(t('Next 6 Months Forecast')); ?></span>
        <i class="fa-solid fa-chart-line"></i>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:12px">
            <thead style="background:#f5f7ff">
                <tr>
                    <th><?= e(t('Month')); ?></th>
                    <?php if ($salaryConfig): ?><th class="text-center"><?= e(t('Days')); ?></th><?php endif; ?>
                    <th class="text-end" style="color:var(--success)"><?= e(t('Est. Income')); ?></th>
                    <th class="text-end" style="color:var(--danger)"><?= e(t('Est. Expense')); ?></th>
                    <th class="text-end"><?= e(t('Est. Savings')); ?></th>
                    <th class="text-end"><?= e(t('Cumulative')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projections as $p): ?>
                    <?php $savingsPositive = $p['est_savings'] >= 0; ?>
                    <tr>
                        <td class="fw-600"><?= e(($mNames[$p['month']] ?? $p['month']) . ' ' . $p['year']); ?></td>
                        <?php if ($salaryConfig): ?>
                            <td class="text-center text-muted"><?= $p['working_days'] > 0 ? (int)$p['working_days'] : '–'; ?></td>
                        <?php endif; ?>
                        <td class="text-end" style="color:var(--success)"><?= e(currency_format((float)$p['est_income'])); ?></td>
                        <td class="text-end" style="color:var(--danger)">
                            <?= e(currency_format((float)$p['est_expense'])); ?>
                            <?php if (($p['recurring_total'] ?? 0) > 0): ?>
                                <div style="font-size:10px;color:var(--muted);line-height:1.2">
                                    <?= e(t('cicilan')); ?>: <?= e(currency_format((float)$p['recurring_total'])); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-700 <?= $savingsPositive ? '' : 'text-danger'; ?>">
                            <?= $savingsPositive ? '' : '−'; ?><?= e(currency_format(abs((float)$p['est_savings']))); ?>
                        </td>
                        <td class="text-end fw-700 <?= $p['cumulative'] >= 0 ? 'text-primary' : 'text-danger'; ?>">
                            <?= $p['cumulative'] >= 0 ? '' : '−'; ?><?= e(currency_format(abs((float)$p['cumulative']))); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="soft-card mb-3" style="font-size:12px;color:var(--muted)">
    <i class="fa-solid fa-circle-info me-1"></i>
    <?= e(t('Income estimates use your active salary config with calculated working days per month. Expense estimates use the average of last 3 months. Projections are estimates only.')); ?>
    <?php if ($historicRecurringAvg > 0): ?>
    <br><i class="fa-solid fa-rotate me-1 mt-1"></i>
    <?= e(t('Expense projection = variable avg. (:var) + active recurring bills per month.',
        ['var' => currency_format($variableExpenseAvg)])); ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function(){
    var labels        = <?= json_encode($chartLabels);  ?>;
    var incomeData    = <?= json_encode($chartIncome);  ?>;
    var expenseData   = <?= json_encode($chartExpense); ?>;
    var histCount     = <?= count($allHistKeys); ?>;          // real months count
    var projCount     = <?= count($projections); ?>;

    // Build border-dash arrays: solid for history, dashed for projection
    var solidDash  = [];
    var dashedDash = [5, 4];
    var incomeBorderDash  = labels.map(function(_,i){ return i < histCount ? solidDash : dashedDash; });
    var expenseBorderDash = labels.map(function(_,i){ return i < histCount ? solidDash : dashedDash; });

    new Chart(document.getElementById('projChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: <?= json_encode(t('Income')); ?>,
                    data: incomeData,
                    backgroundColor: labels.map(function(_,i){
                        return i < histCount ? 'rgba(8,145,178,0.7)' : 'rgba(8,145,178,0.25)';
                    }),
                    borderColor: 'rgba(8,145,178,1)',
                    borderWidth: 1.5,
                    borderRadius: 4,
                },
                {
                    label: <?= json_encode(t('Expense')); ?>,
                    data: expenseData,
                    backgroundColor: labels.map(function(_,i){
                        return i < histCount ? 'rgba(220,38,38,0.6)' : 'rgba(220,38,38,0.2)';
                    }),
                    borderColor: 'rgba(220,38,38,0.9)',
                    borderWidth: 1.5,
                    borderRadius: 4,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
            scales: {
                x: { ticks: { font: { size: 10 } } },
                y: {
                    ticks: {
                        font: { size: 10 },
                        callback: function(v){ return 'IDR ' + (v/1000000).toFixed(1) + 'M'; }
                    }
                }
            }
        }
    });
})();
</script>
