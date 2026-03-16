<?php
$isEdit = ($record !== null);
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
$selYear  = $isEdit ? (int)$record['period_year']  : (int)date('Y');
$selMonth = $isEdit ? (int)$record['period_month'] : (int)date('n');
$configCalcUrl = base_url('/salary-config/calculate');
$configRateMap = [];
foreach ($configs ?? [] as $cfg) {
    $configRateMap[(int)$cfg['id']] = [
        'meal' => (float) ($cfg['meal_allowance_per_day'] ?? 0),
        'transport' => (float) ($cfg['transport_allowance_per_day'] ?? 0),
    ];
}
?>

<section class="mb-3">
    <h4 class="mb-0"><?= e($isEdit ? t('Edit Income') : t('Add Income')); ?></h4>
</section>

<div class="soft-card">
    <form method="post"
          action="<?= e($isEdit ? base_url('/income/update') : base_url('/income/store')); ?>"
          class="vstack gap-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$record['id']; ?>">
        <?php endif; ?>

        <!-- ─── Source name ─── -->
        <div>
            <label class="form-label"><?= e(t('Income Source')); ?></label>
            <input type="text" name="source_name" class="form-control"
                   value="<?= e($isEdit ? $record['source_name'] : t('Salary')); ?>"
                   placeholder="<?= e(t('e.g. Salary, Freelance')); ?>" required>
        </div>

        <!-- ─── Period ─── -->
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label"><?= e(t('Month')); ?></label>
                <select name="period_month" id="periodMonth" class="form-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m; ?>" <?= $selMonth === $m ? 'selected' : ''; ?>>
                            <?= e($mNames[$m]); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label"><?= e(t('Year')); ?></label>
                <input type="number" name="period_year" id="periodYear" class="form-control"
                       value="<?= $selYear; ?>" min="2000" max="2099" required>
            </div>
        </div>

        <!-- ─── Salary config auto-fill ─── -->
        <?php if (!empty($configs)): ?>
            <div class="soft-card" style="background:#f5f7ff;box-shadow:none;padding:12px">
                <label class="form-label fw-bold mb-2">
                    <i class="fa-solid fa-wand-magic-sparkles me-1 text-primary"></i>
                    <?= e(t('Auto-fill from Salary Config')); ?>
                </label>
                <div class="d-flex gap-2">
                    <select id="autoConfigSelect" class="form-select">
                        <option value=""><?= e(t('Select config…')); ?></option>
                        <?php foreach ($configs as $cfg): ?>
                            <option value="<?= (int)$cfg['id']; ?>"
                                <?= ($isEdit && $record['salary_config_id'] == $cfg['id']) ? 'selected' : ''; ?>>
                                <?= e($cfg['name']); ?>
                                <?= $cfg['is_active'] ? ' ✓' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="autoCalcBtn" class="btn btn-primary" style="white-space:nowrap">
                        <i class="fa-solid fa-calculator me-1"></i><?= e(t('Calculate')); ?>
                    </button>
                </div>
                <div id="calcPeriodInfo" class="mt-2" style="font-size:11px;color:var(--muted);display:none"></div>
                <!-- Deduction breakdown (shown after Calculate) -->
                <div id="deductionBreakdown" style="display:none;margin-top:8px;background:#fff5f5;border:1px solid #fecaca;border-radius:8px;padding:8px">
                    <div class="fw-bold mb-1" style="font-size:12px;color:#dc2626">
                        <i class="fa-solid fa-scissors me-1"></i><?= $isEn ? 'Deductions' : 'Potongan Gaji'; ?>
                    </div>
                    <div id="deductionRows" class="vstack gap-1" style="font-size:12px"></div>
                    <div class="d-flex justify-content-between border-top pt-1 mt-1">
                        <span style="font-size:12px;color:var(--muted)"><?= $isEn ? 'Total Deductions' : 'Total Potongan'; ?></span>
                        <span id="totalDeductionsDisplay" class="fw-bold text-danger" style="font-size:12px"></span>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="fw-bold" style="font-size:13px;color:var(--success)"><?= $isEn ? 'Net Income (Take-home)' : 'Gaji Bersih (Take-home)'; ?></span>
                        <span id="netIncomeDisplay" class="fw-bold" style="font-size:14px;color:var(--success)"></span>
                    </div>
                </div>
            </div>
            <input type="hidden" name="salary_config_id" id="salaryConfigId"
                   value="<?= $isEdit ? (int)($record['salary_config_id'] ?? 0) : 0; ?>">
                <input type="hidden" name="total_deductions" id="totalDeductionsHidden"
                       value="<?= $isEdit ? (float)($record['total_deductions'] ?? 0) : 0; ?>">
            <?php else: ?>
                <input type="hidden" name="salary_config_id" value="">
                <input type="hidden" name="total_deductions" id="totalDeductionsHidden" value="0">
            <?php endif; ?>

        <!-- ─── Working days ─── -->
        <div>
            <label class="form-label"><?= e(t('Working Days')); ?></label>
            <input type="number" name="working_days" id="workingDays" class="form-control"
                   value="<?= $isEdit ? (int)$record['working_days'] : 0; ?>" min="0" max="31">
            <div class="form-text"><?= e(t('Auto-filled when using Calculate above')); ?></div>
        </div>

        <!-- ─── Salary components ─── -->
        <div>
            <label class="form-label"><?= e(t('Base Salary')); ?></label>
             <input type="text" inputmode="numeric" name="base_salary" id="baseSalary" class="form-control income-comp fmt-idr"
                 value="<?= $isEdit ? number_format((float)$record['base_salary'], 0, ',', '.') : '0'; ?>">
        </div>

        <div class="card" style="border:1.5px solid #e0e7ff;border-radius:12px;padding:12px">
            <div class="fw-600 mb-2" style="font-size:13px;color:var(--primary)">
                <i class="fa-solid fa-layer-group me-1"></i><?= e(t('Allowances')); ?>
            </div>
            <div class="vstack gap-2">
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted" style="font-size:12px;min-width:110px"><?= e(t('Meal Allow.')); ?></label>
                      <input type="text" inputmode="numeric" name="meal_allowance" id="mealAllowance" class="form-control form-control-sm income-comp fmt-idr"
                          value="<?= $isEdit ? number_format((float)$record['meal_allowance'], 0, ',', '.') : '0'; ?>">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted" style="font-size:12px;min-width:110px"><?= e(t('Transport Allow.')); ?></label>
                      <input type="text" inputmode="numeric" name="transport_allowance" id="transportAllowance" class="form-control form-control-sm income-comp fmt-idr"
                          value="<?= $isEdit ? number_format((float)$record['transport_allowance'], 0, ',', '.') : '0'; ?>">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted" style="font-size:12px;min-width:110px"><?= e(t('Position Allow.')); ?></label>
                      <input type="text" inputmode="numeric" name="position_allowance" id="positionAllowance" class="form-control form-control-sm income-comp fmt-idr"
                          value="<?= $isEdit ? number_format((float)$record['position_allowance'], 0, ',', '.') : '0'; ?>">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted" style="font-size:12px;min-width:110px"><?= e(t('Other Income')); ?></label>
                      <input type="text" inputmode="numeric" name="other_income" id="otherIncome" class="form-control form-control-sm income-comp fmt-idr"
                          value="<?= $isEdit ? number_format((float)$record['other_income'], 0, ',', '.') : '0'; ?>">
                </div>
            </div>
        </div>

        <!-- ─── Total (readonly, auto-calculated) ─── -->
        <div>
            <label class="form-label fw-bold"><?= e(t('Total Income')); ?> <span id="grossLabel" class="text-muted fw-normal" style="font-size:11px;display:none">(<?= $isEn ? 'Gross' : 'Kotor'; ?>)</span></label>
            <div class="input-group">
                <span class="input-group-text" style="background:var(--success-light);color:var(--success);font-weight:700">
                    <?= e($_SESSION['user']['currency'] ?? 'IDR'); ?>
                </span>
                <input type="text" id="totalIncomeDisplay" class="form-control fw-bold"
                       style="font-size:18px;color:var(--success);background:#f0fdf4" readonly>
            </div>
        </div>
        <!-- Net income display (shown when deductions > 0) -->
        <div id="netIncomeBox" style="display:none">
            <label class="form-label fw-bold" style="color:var(--primary)"><?= $isEn ? 'Net Income (Take-home)' : 'Gaji Bersih (Take-home)'; ?></label>
            <div class="input-group">
                <span class="input-group-text" style="background:#ede9fe;color:var(--primary);font-weight:700">
                    <?= e($_SESSION['user']['currency'] ?? 'IDR'); ?>
                </span>
                <input type="text" id="netIncomeBoxDisplay" class="form-control fw-bold"
                       style="font-size:18px;color:var(--primary);background:#f5f3ff" readonly>
            </div>
        </div>

        <!-- ─── Received date ─── -->
        <div>
            <label class="form-label"><?= e(t('Received Date')); ?> (<?= e(t('optional')); ?>)</label>
            <input type="date" name="received_date" class="form-control"
                   value="<?= e($isEdit && $record['received_date'] ? $record['received_date'] : ''); ?>">
        </div>

        <!-- ─── Notes ─── -->
        <div>
            <label class="form-label"><?= e(t('Notes')); ?> (<?= e(t('optional')); ?>)</label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="<?= e(t('Optional note')); ?>"><?= e($isEdit ? ($record['notes'] ?? '') : ''); ?></textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="fa-solid fa-floppy-disk me-1"></i>
                <?= e($isEdit ? t('Update Income') : t('Save Income')); ?>
            </button>
            <a href="<?= e(base_url('/income')); ?>" class="btn btn-light"><?= e(t('Cancel')); ?></a>
        </div>
    </form>
</div>

<script>
(function () {
    var calcUrl  = <?= json_encode($configCalcUrl); ?>;
    var configRateMap = <?= json_encode($configRateMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var compIds  = ['baseSalary','mealAllowance','transportAllowance','positionAllowance','otherIncome'];
    var fmtNum   = function(n){ return Math.round(n).toLocaleString('id-ID'); };
    var selectedConfigId = <?= json_encode((string) ($isEdit ? (int)($record['salary_config_id'] ?? 0) : 0)); ?>;
    var initialRates = configRateMap[selectedConfigId] || null;
    var mealPerDayRate = initialRates ? Number(initialRates.meal || 0) : 0;
    var transportPerDayRate = initialRates ? Number(initialRates.transport || 0) : 0;
    var parseNum = function(v){
        var normalized = String(v || '').replace(/\./g, '').replace(/,/g, '.');
        return parseFloat(normalized) || 0;
    };
    var stripNum = function(v){
        return String(v || '').replace(/\D/g, '');
    };
    var bindFmtIdr = function(el){
        if (el.value) {
            el.value = fmtNum(parseNum(el.value));
        }
        el.addEventListener('focus', function(){
            this.value = stripNum(this.value);
        });
        el.addEventListener('blur', function(){
            this.value = fmtNum(parseNum(this.value));
            recalcTotal();
        });
    };

    function recalcTotal() {
        var total = 0;
        compIds.forEach(function(id){
            total += parseNum(document.getElementById(id).value);
        });
        document.getElementById('totalIncomeDisplay').value = fmtNum(total);

        var totalDeductions = parseNum(document.getElementById('totalDeductionsHidden').value);
        if (totalDeductions > 0) {
            document.getElementById('netIncomeDisplay').textContent = fmtNum(total - totalDeductions);
            document.getElementById('netIncomeBoxDisplay').value = fmtNum(total - totalDeductions);
        }
    }

    function recalcFromWorkingDays() {
        var workingDaysEl = document.getElementById('workingDays');
        if (!workingDaysEl) return;

        var workingDays = parseInt(workingDaysEl.value || '0', 10) || 0;
        if (mealPerDayRate > 0) {
            document.getElementById('mealAllowance').value = fmtNum(Math.round(mealPerDayRate * workingDays));
        }
        if (transportPerDayRate > 0) {
            document.getElementById('transportAllowance').value = fmtNum(Math.round(transportPerDayRate * workingDays));
        }
        recalcTotal();
    }

    function syncRatesFromSelectedConfig() {
        var autoConfig = document.getElementById('autoConfigSelect');
        if (!autoConfig) return;
        var configRates = configRateMap[String(autoConfig.value || '')] || null;
        if (!configRates) return;
        mealPerDayRate = Number(configRates.meal || 0);
        transportPerDayRate = Number(configRates.transport || 0);
    }

    document.querySelectorAll('.income-comp').forEach(function(el){
        el.addEventListener('input', recalcTotal);
    });
    document.querySelectorAll('.fmt-idr').forEach(bindFmtIdr);

    var workingDaysEl = document.getElementById('workingDays');
    if (workingDaysEl) {
        workingDaysEl.addEventListener('input', recalcFromWorkingDays);
        workingDaysEl.addEventListener('change', recalcFromWorkingDays);
    }

    var autoConfigSelect = document.getElementById('autoConfigSelect');
    if (autoConfigSelect) {
        autoConfigSelect.addEventListener('change', syncRatesFromSelectedConfig);
    }

    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            form.querySelectorAll('.fmt-idr').forEach(function(el) {
                el.value = stripNum(el.value);
            });
        });
    }

    recalcTotal();

    var calcBtn = document.getElementById('autoCalcBtn');
    if (!calcBtn) return;

    calcBtn.addEventListener('click', function() {
        var configId = document.getElementById('autoConfigSelect').value;
        var year     = document.getElementById('periodYear').value;
        var month    = document.getElementById('periodMonth').value;

        if (!configId) { alert(<?= json_encode(t('Please select a salary config.')); ?>); return; }

        calcBtn.disabled = true;
        calcBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i><?= e(t('Calculating…')); ?>';

        var url = calcUrl + '?config_id=' + configId + '&year=' + year + '&month=' + month;
        fetch(url)
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data.error) { alert(data.error); return; }
                document.getElementById('salaryConfigId').value      = configId;
                document.getElementById('workingDays').value         = data.working_days;
                document.getElementById('baseSalary').value          = fmtNum(data.base_salary);
                document.getElementById('mealAllowance').value       = fmtNum(data.meal_allowance);
                document.getElementById('transportAllowance').value  = fmtNum(data.transport_allowance);
                document.getElementById('positionAllowance').value   = fmtNum(data.position_allowance);
                mealPerDayRate = Number(data.meal_allowance_per_day || 0);
                transportPerDayRate = Number(data.transport_allowance_per_day || 0);
                recalcTotal();
                var info = document.getElementById('calcPeriodInfo');
                info.style.display = 'block';
                info.textContent = '<?= e(t('Period')); ?>: ' + data.period_start + ' → ' + data.period_end
                    + '  |  ' + data.working_days + ' <?= e(t('work days')); ?>';

                // Deductions
                var dedBreakdown = document.getElementById('deductionBreakdown');
                var dedRows      = document.getElementById('deductionRows');
                var hiddenDed    = document.getElementById('totalDeductionsHidden');
                dedRows.innerHTML = '';
                if (data.deductions && data.deductions.length > 0) {
                    data.deductions.forEach(function(d) {
                        var row = '<div class="d-flex justify-content-between">'
                            + '<span>' + d.name + ' <span class="text-muted" style="font-size:11px">(' + d.formula + ')</span></span>'
                            + '<span class="text-danger fw-bold">-' + fmtNum(d.amount) + '</span></div>';
                        dedRows.innerHTML += row;
                    });
                    document.getElementById('totalDeductionsDisplay').textContent = fmtNum(data.total_deductions);
                    document.getElementById('netIncomeDisplay').textContent       = fmtNum(data.net_income);
                    document.getElementById('netIncomeBoxDisplay').textContent    = fmtNum(data.net_income);
                    hiddenDed.value = data.total_deductions;
                    dedBreakdown.style.display = 'block';
                    document.getElementById('netIncomeBox').style.display = 'block';
                    document.getElementById('grossLabel').style.display   = 'inline';
                } else {
                    dedBreakdown.style.display = 'none';
                    document.getElementById('netIncomeBox').style.display = 'none';
                    document.getElementById('grossLabel').style.display   = 'none';
                    hiddenDed.value = 0;
                }
            })
            .catch(function(){ alert(<?= json_encode(t('Calculation failed.')); ?>); })
            .finally(function(){
                calcBtn.disabled = false;
                calcBtn.innerHTML = '<i class="fa-solid fa-calculator me-1"></i><?= e(t('Calculate')); ?>';
            });
    });
})();
</script>
