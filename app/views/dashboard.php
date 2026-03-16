<?php
$isEnglish = current_language() === 'en';
$labels = language_options();
$activeLabel = $labels[current_language()] ?? 'English';
$activeFlag = $isEnglish ? '🇬🇧' : '🇮🇩';
$redirectPath = current_path();
if (!empty($_SERVER['QUERY_STRING'])) {
    $redirectPath .= '?' . (string) $_SERVER['QUERY_STRING'];
}
$monthNames = $isEnglish
    ? [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December']
    : [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$currentPeriodLabel = ($monthNames[(int) ($currentMonth ?? date('n'))] ?? '') . ' ' . (int) ($currentYear ?? date('Y'));
$latestIncomePeriodLabel = '';
if (!empty($latestIncomeMonth)) {
    $latestIncomePeriodLabel = ($monthNames[(int) $latestIncomeMonth['month']] ?? '') . ' ' . (int) $latestIncomeMonth['year'];
}
?>

<div class="dashboard-compact">
<section class="hero-card hero-card-compact mb-2">
    <div class="hero-lang-corner">
        <form method="post" action="<?= e(base_url('/language/switch')); ?>" class="lang-switch-compact">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <input type="hidden" name="redirect" value="<?= e($redirectPath); ?>">
            <input type="hidden" name="lang" value="<?= e(current_language()); ?>">

            <span class="lang-flag <?= !$isEnglish ? 'active' : ''; ?>" aria-hidden="true">ID</span>
            <div class="form-check form-switch m-0">
                <input class="form-check-input lang-toggle-input" type="checkbox" role="switch" <?= $isEnglish ? 'checked' : ''; ?>
                    onchange="this.form.querySelector('[name=lang]').value=this.checked?'en':'id'; this.form.submit();">
            </div>
            <span class="lang-flag <?= $isEnglish ? 'active' : ''; ?>" aria-hidden="true">EN</span>
        </form>

        <div class="lang-active-badge">
            <span class="lang-active-flag" aria-hidden="true"><?= e($activeFlag); ?></span>
            <span><?= e($activeLabel); ?></span>
        </div>
    </div>

    <p class="hero-greeting mb-0"><?= e(t('Good')); ?> <?= e(t((int) date('H') < 12 ? 'morning' : ((int) date('H') < 18 ? 'afternoon' : 'evening'))); ?>,</p>
    <h2 class="hero-name mb-0"><?= e($user['name']); ?></h2>
</section>

<section class="row g-2">
    <div class="col-6">
        <div class="summary-card summary-card--cyan h-100">
            <div class="summary-card-head">
                <div class="summary-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <div class="summary-card-copy">
                    <span><?= e(t('Income This Month')); ?></span>
                    <small><?= e($currentPeriodLabel); ?></small>
                </div>
            </div>
            <div class="summary-card-bodyline">
                <h6 class="summary-card-amount summary-card-amount--success"><?= e(currency_format((float) $monthIncome)); ?></h6>
            </div>
            <?php if (!empty($latestIncomeMonth) && ((int) $latestIncomeMonth['month'] !== (int) $currentMonth || (int) $latestIncomeMonth['year'] !== (int) $currentYear)): ?>
                <small class="summary-card-subnote"><?= e(t('Latest income record: :period.', ['period' => $latestIncomePeriodLabel])); ?></small>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-6">
        <div class="summary-card summary-card--blue h-100">
            <div class="summary-card-head">
                <div class="summary-icon"><i class="fa-solid fa-fire-flame-curved"></i></div>
                <div class="summary-card-copy">
                    <span><?= e(t('Expense This Month')); ?></span>
                    <small>&nbsp;</small>
                </div>
            </div>
            <div class="summary-card-bodyline">
                <h6 class="summary-card-amount"><?= e(currency_format((float) $monthExpense)); ?></h6>
            </div>
            <small class="summary-card-subnote">&nbsp;</small>
        </div>
    </div>
    <div class="col-6">
        <div class="summary-card <?= $balance >= 0 ? 'summary-card--purple' : 'summary-warning'; ?> h-100">
            <div class="summary-card-head">
                <div class="summary-icon"><i class="fa-solid fa-wallet"></i></div>
                <div class="summary-card-copy">
                    <span><?= e(t('Balance')); ?></span>
                    <small>&nbsp;</small>
                </div>
            </div>
            <div class="summary-card-bodyline">
                <h6 class="summary-card-amount <?= $balance < 0 ? 'text-danger' : ''; ?>">
                    <?= e(currency_format(abs((float) $balance))); ?>
                    <?php if ($monthIncome > 0): ?>
                        <small class="summary-card-rate"><?= $balance >= 0 ? '+' : '−'; ?><?= (float)$savingsRate; ?>%</small>
                    <?php endif; ?>
                </h6>
            </div>
            <?php if (!empty($carryover)): ?>
                <small class="summary-card-subnote">
                    <?= e(t('Prev. balance: :amt', ['amt' => ($carryover >= 0 ? '+' : '−') . currency_format(abs((float) $carryover))])); ?>
                </small>
            <?php else: ?>
                <small class="summary-card-subnote">&nbsp;</small>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-6">
        <div class="summary-card summary-card--warning <?= $remainingPct < 20 ? 'summary-warning' : ''; ?> h-100">
            <div class="summary-card-head">
                <div class="summary-icon"><i class="fa-solid fa-bullseye"></i></div>
                <div class="summary-card-copy">
                    <span><?= e(t('Budget Remaining')); ?></span>
                    <small>&nbsp;</small>
                </div>
            </div>
            <div class="summary-card-bodyline">
                <h6 class="summary-card-amount"><?= e(currency_format((float) $remaining)); ?></h6>
            </div>
            <small class="summary-card-subnote">&nbsp;</small>
        </div>
    </div>
</section>

<?php require APP_PATH . '/views/components/menu_grid.php'; ?>

<?php if (!empty($insights)): ?>
    <section class="mt-2">
        <?php foreach ($insights as $insight): ?>
            <div class="insight-item">
                <i class="fa-solid fa-lightbulb"></i>
                <span><?= e($insight); ?></span>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<section class="mt-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0"><?= e(t('Recent Transactions')); ?></h5>
        <a href="<?= e(base_url('/transactions')); ?>" class="small"><?= e(t('See all')); ?></a>
    </div>

    <?php if (empty($recentTransactions)): ?>
        <div class="soft-card text-center text-muted py-3"><?= e(t('No transactions yet.')); ?></div>
    <?php else: ?>
        <div class="vstack gap-2">
            <?php foreach ($recentTransactions as $tx): ?>
                <div class="transaction-item">
                    <div class="tx-icon"><i class="fa-solid <?= e($tx['category_icon']); ?>"></i></div>
                    <div class="tx-body">
                        <strong><?= e($tx['category_name']); ?></strong>
                        <small><?= e($tx['transaction_date']); ?> • <?= e($tx['payment_method_name']); ?></small>
                    </div>
                    <div class="tx-amount"><?= e(currency_format((float) $tx['amount'])); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
</div>
