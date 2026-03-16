<?php
$isEn = current_language() === 'en';

/**
 * Render deductions sub-form.
 * $prefix   = unique DOM prefix (e.g. 'add' or 'edit-5')
 * $existing = pre-populated deduction rows
 */
function renderDeductionsSection(string $prefix, array $existing = []): void {
    $isEn = current_language() === 'en';
    $baseTypeOpts = [
        'basic_fixed' => $isEn ? 'Base + Fixed Allow.' : 'Gaji Pokok + Tunj. Tetap',
        'basic_only'  => $isEn ? 'Base Salary only'    : 'Gaji Pokok saja',
    ];
    ?>
    <div style="border:1px solid #fde68a;border-radius:10px;padding:10px;background:#fffbeb">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold" style="font-size:13px;color:#b45309">
                <i class="fa-solid fa-scissors me-1"></i><?= $isEn ? 'Deductions' : 'Potongan Gaji'; ?>
            </span>
            <button type="button" class="btn btn-sm"
                    style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;font-size:12px"
                    onclick="addDedRow('<?= e($prefix); ?>')">
                <?= $isEn ? '+ Add Deduction' : '+ Tambah Potongan'; ?>
            </button>
        </div>
        <div id="ded-rows-<?= e($prefix); ?>" class="vstack gap-2">
            <?php foreach ($existing as $ded): ?>
            <div class="ded-row" style="background:#fff;border:1px solid #fde68a;border-radius:8px;padding:8px">
                <div class="row g-1">
                    <div class="col-12">
                        <label style="font-size:11px;color:var(--muted)"><?= $isEn ? 'Name' : 'Nama'; ?></label>
                        <input type="text" name="ded_name[]" class="form-control form-control-sm"
                               value="<?= e($ded['name']); ?>" required>
                    </div>
                    <div class="col-6">
                        <label style="font-size:11px;color:var(--muted)"><?= $isEn ? 'Type' : 'Tipe'; ?></label>
                        <select name="ded_type[]" class="form-select form-select-sm" onchange="toggleDedType(this)">
                            <option value="percentage" <?= $ded['type']==='percentage'?'selected':''; ?>>% <?= $isEn?'Percentage':'Persentase'; ?></option>
                            <option value="fixed"      <?= $ded['type']==='fixed'?'selected':''; ?>>Rp <?= $isEn?'Fixed':'Nominal Tetap'; ?></option>
                        </select>
                    </div>
                    <div class="col-6 ded-pct-group <?= $ded['type']==='fixed'?'d-none':''; ?>">
                        <label style="font-size:11px;color:var(--muted)">Rate (%)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="ded_rate[]" class="form-control form-control-sm"
                                   value="<?= (float)($ded['rate']??0); ?>" min="0" max="100" step="0.01">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-6 ded-fixed-group <?= $ded['type']!=='fixed'?'d-none':''; ?>">
                        <label style="font-size:11px;color:var(--muted)"><?= $isEn?'Amount (Rp)':'Jumlah (Rp)'; ?></label>
                        <input type="text" inputmode="numeric" name="ded_fixed_amount[]" class="form-control form-control-sm fmt-idr"
                               value="<?= (float)($ded['fixed_amount']??0) > 0 ? number_format((float)$ded['fixed_amount'], 0, ',', '.') : ''; ?>">
                    </div>
                    <div class="col-12 ded-pct-group <?= $ded['type']==='fixed'?'d-none':''; ?>">
                        <label style="font-size:11px;color:var(--muted)"><?= $isEn?'Base':'Dasar Perhitungan'; ?></label>
                        <select name="ded_base_type[]" class="form-select form-select-sm">
                            <?php foreach ($baseTypeOpts as $val => $lbl): ?>
                                <option value="<?= $val; ?>" <?= ($ded['base_type']===$val)?'selected':''; ?>><?= e($lbl); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 ded-pct-group <?= $ded['type']==='fixed'?'d-none':''; ?>">
                        <label style="font-size:11px;color:var(--muted)"><?= $isEn?'Salary Cap (max base, 0=no cap)':'Batas Maks Gaji (0=tanpa batas)'; ?></label>
                        <input type="text" inputmode="numeric" name="ded_base_cap[]" class="form-control form-control-sm fmt-idr"
                               value="<?= (float)($ded['base_cap']??0) > 0 ? number_format((float)$ded['base_cap'], 0, ',', '.') : ''; ?>">
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" style="font-size:11px"
                                onclick="this.closest('.ded-row').remove()"><?= $isEn?'Remove':'Hapus'; ?></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-2 pt-1">
            <button type="button" class="btn btn-sm w-100"
                    style="background:#fff;color:#b45309;border:1px dashed #f59e0b;font-size:12px"
                    onclick="addDedRow('<?= e($prefix); ?>')">
                <i class="fa-solid fa-plus me-1"></i><?= $isEn ? 'Add another deduction' : 'Tambah potongan lagi'; ?>
            </button>
        </div>
    </div>
    <?php
}
?>

<section class="mb-3 d-flex align-items-center justify-content-between">
    <h4 class="mb-0"><?= e(t('Salary Config')); ?></h4>
    <a href="<?= e(base_url('/income')); ?>" class="btn btn-sm btn-outline-primary">
        <i class="fa-solid fa-money-bill-wave me-1"></i><?= e(t('Income Records')); ?>
    </a>
</section>

<!-- ─── Add config form ─── -->
<details class="soft-card mb-3" id="salary-add-details" <?= empty($configs) ? 'open' : ''; ?>>
    <summary class="fw-bold mb-0" style="cursor:pointer;list-style:none">
        <span><i class="fa-solid fa-plus-circle me-1 text-primary"></i><?= e(t('Add Salary Config')); ?></span>
    </summary>
    <form method="post" action="<?= e(base_url('/salary-config/store')); ?>" class="vstack gap-2 mt-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

        <div>
            <label class="form-label"><?= e(t('Config Name')); ?></label>
            <input type="text" name="name" class="form-control" placeholder="<?= e(t('e.g. Gaji PT XYZ')); ?>" required>
        </div>

        <div>
            <label class="form-label"><?= e(t('Base Salary')); ?> (<?= e(t('per month')); ?>)</label>
            <input type="text" inputmode="numeric" name="base_salary" class="form-control fmt-idr" placeholder="0" required>
        </div>

        <div class="row g-2">
            <div class="col-6">
                <label class="form-label"><?= e(t('Meal Allow.')); ?> / <?= e(t('day')); ?></label>
                <input type="text" inputmode="numeric" name="meal_allowance_per_day" class="form-control fmt-idr" placeholder="0">
            </div>
            <div class="col-6">
                <label class="form-label"><?= e(t('Transport Allow.')); ?> / <?= e(t('day')); ?></label>
                <input type="text" inputmode="numeric" name="transport_allowance_per_day" class="form-control fmt-idr" placeholder="0">
            </div>
        </div>

        <div>
            <label class="form-label"><?= e(t('Position Allow.')); ?> (<?= e(t('fixed/month')); ?>)</label>
            <input type="text" inputmode="numeric" name="position_allowance" class="form-control fmt-idr" placeholder="0">
            <div class="form-text"><?= $isEn ? 'Tunjangan Tetap – used as base for BPJS-type deductions.' : 'Tunjangan Tetap – dasar perhitungan potongan BPJS.'; ?></div>
        </div>

        <?php renderDeductionsSection('add', []); ?>

        <div class="row g-2">
            <div class="col-6">
                <label class="form-label"><?= e(t('Cutoff Day')); ?></label>
                <select name="cutoff_day" class="form-select">
                    <option value="0"><?= e(t('End of month')); ?></option>
                    <?php for ($d = 1; $d <= 28; $d++): ?>
                        <option value="<?= $d; ?>"><?= e(t('Day :n', ['n' => $d])); ?></option>
                    <?php endfor; ?>
                </select>
                <div class="form-text"><?= e(t('Cutoff day for attendance allowance calculation')); ?></div>
            </div>
            <div class="col-6">
                <label class="form-label"><?= e(t('Working Days / Week')); ?></label>
                <select name="working_days_per_week" class="form-select">
                    <option value="5">5 (<?= e(t('Mon–Fri')); ?>)</option>
                    <option value="6">6 (<?= e(t('Mon–Sat')); ?>)</option>
                </select>
            </div>
        </div>

        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="addIsActive" value="1">
            <label class="form-check-label" for="addIsActive"><?= e(t('Set as active config')); ?></label>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i><?= e(t('Save')); ?>
        </button>
    </form>
</details>

<!-- ─── Config list ─── -->
<?php if (empty($configs)): ?>
    <div class="soft-card text-center text-muted py-4">
        <i class="fa-solid fa-building mb-2 d-block" style="font-size:28px;opacity:.3"></i>
        <?= e(t('No salary config yet. Add one above.')); ?>
    </div>
<?php else: ?>
    <div class="vstack gap-2">
        <?php foreach ($configs as $cfg): ?>
            <div class="soft-card">

                <!-- Header row -->
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                        <strong class="d-block"><?= e($cfg['name']); ?></strong>
                        <?php if ($cfg['is_active']): ?>
                            <span class="badge text-bg-success"><?= e(t('Active')); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-light"
                            onclick="(function(){var el=document.getElementById('edit-<?= (int)$cfg['id']; ?>');el.style.display=el.style.display==='block'?'none':'block';})()">
                            <i class="fa-solid fa-pencil"></i>
                        </button>
                        <form method="post" action="<?= e(base_url('/salary-config/delete')); ?>"
                              onsubmit="return confirm('<?= e(t('Delete salary config?')); ?>')">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?= (int)$cfg['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Summary grid -->
                <div class="row g-1" style="font-size:12px">
                    <div class="col-6 text-muted"><?= e(t('Base Salary')); ?></div>
                    <div class="col-6 fw-600"><?= e(currency_format((float)$cfg['base_salary'])); ?></div>
                    <div class="col-6 text-muted"><?= e(t('Meal Allow.')); ?>/<?= e(t('day')); ?></div>
                    <div class="col-6 fw-600"><?= e(currency_format((float)$cfg['meal_allowance_per_day'])); ?></div>
                    <div class="col-6 text-muted"><?= e(t('Transport Allow.')); ?>/<?= e(t('day')); ?></div>
                    <div class="col-6 fw-600"><?= e(currency_format((float)$cfg['transport_allowance_per_day'])); ?></div>
                    <div class="col-6 text-muted"><?= e(t('Position Allow.')); ?></div>
                    <div class="col-6 fw-600"><?= e(currency_format((float)$cfg['position_allowance'])); ?></div>
                    <div class="col-6 text-muted"><?= e(t('Cutoff Day')); ?></div>
                    <div class="col-6 fw-600">
                        <?= $cfg['cutoff_day'] == 0
                            ? e(t('End of month'))
                            : e(t('Day :n', ['n' => (int)$cfg['cutoff_day']])); ?>
                    </div>
                    <div class="col-6 text-muted"><?= e(t('Working Days / Week')); ?></div>
                    <div class="col-6 fw-600"><?= (int)$cfg['working_days_per_week']; ?> <?= e(t('days')); ?></div>
                </div>

                <!-- Deductions summary -->
                <?php if (!empty($cfg['deductions'])): ?>
                    <div class="mt-2 pt-2 border-top" style="font-size:12px">
                        <div class="fw-bold mb-1" style="color:#b45309">
                            <i class="fa-solid fa-scissors me-1"></i><?= $isEn ? 'Deductions' : 'Potongan'; ?>
                        </div>
                        <?php foreach ($cfg['deductions'] as $ded): ?>
                            <?php
                            if ($ded['type'] === 'fixed') {
                                $fmla = currency_format((float)$ded['fixed_amount']);
                            } else {
                                $bLabel  = $ded['base_type'] === 'basic_only'
                                    ? ($isEn ? 'Base' : 'G.Pokok')
                                    : ($isEn ? 'Base+Fix.Allow' : 'Pokok+Jabatan');
                                $capStr  = $ded['base_cap'] ? ' max '.number_format((float)$ded['base_cap'],0,',','.') : '';
                                $rateStr = rtrim(rtrim(number_format((float)$ded['rate'],4,'.',''),'0'),'.');
                                $fmla    = '('.$bLabel.$capStr.') × '.$rateStr.'%';
                            }
                            ?>
                            <div class="d-flex justify-content-between align-items-baseline">
                                <span style="color:#78350f"><?= e($ded['name']); ?></span>
                                <span style="font-size:11px;color:var(--muted)"><?= e($fmla); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Inline edit form -->
                <div id="edit-<?= (int)$cfg['id']; ?>" style="display:none" class="border-top mt-2 pt-2">
                    <form method="post" action="<?= e(base_url('/salary-config/update')); ?>" class="vstack gap-2">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?= (int)$cfg['id']; ?>">

                        <div>
                            <label class="form-label form-label-sm"><?= e(t('Config Name')); ?></label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                   value="<?= e($cfg['name']); ?>" required>
                        </div>
                        <div>
                            <label class="form-label form-label-sm"><?= e(t('Base Salary')); ?></label>
                            <input type="text" inputmode="numeric" name="base_salary" class="form-control form-control-sm fmt-idr"
                                   value="<?= number_format((float)$cfg['base_salary'], 0, ',', '.'); ?>" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label form-label-sm"><?= e(t('Meal Allow.')); ?>/<?= e(t('day')); ?></label>
                                <input type="text" inputmode="numeric" name="meal_allowance_per_day" class="form-control form-control-sm fmt-idr"
                                       value="<?= number_format((float)$cfg['meal_allowance_per_day'], 0, ',', '.'); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label form-label-sm"><?= e(t('Transport Allow.')); ?>/<?= e(t('day')); ?></label>
                                <input type="text" inputmode="numeric" name="transport_allowance_per_day" class="form-control form-control-sm fmt-idr"
                                       value="<?= number_format((float)$cfg['transport_allowance_per_day'], 0, ',', '.'); ?>">
                            </div>
                        </div>
                        <div>
                            <label class="form-label form-label-sm"><?= e(t('Position Allow.')); ?></label>
                            <input type="text" inputmode="numeric" name="position_allowance" class="form-control form-control-sm fmt-idr"
                                   value="<?= number_format((float)$cfg['position_allowance'], 0, ',', '.'); ?>">
                        </div>

                        <?php renderDeductionsSection('edit-'.(int)$cfg['id'], $cfg['deductions'] ?? []); ?>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label form-label-sm"><?= e(t('Cutoff Day')); ?></label>
                                <select name="cutoff_day" class="form-select form-select-sm">
                                    <option value="0" <?= $cfg['cutoff_day']==0?'selected':''; ?>><?= e(t('End of month')); ?></option>
                                    <?php for ($d=1;$d<=28;$d++): ?>
                                        <option value="<?= $d; ?>" <?= $cfg['cutoff_day']==$d?'selected':''; ?>><?= e(t('Day :n',['n'=>$d])); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label form-label-sm"><?= e(t('Working Days / Week')); ?></label>
                                <select name="working_days_per_week" class="form-select form-select-sm">
                                    <option value="5" <?= $cfg['working_days_per_week']==5?'selected':''; ?>>5 (Mon–Fri)</option>
                                    <option value="6" <?= $cfg['working_days_per_week']==6?'selected':''; ?>>6 (Mon–Sat)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="editActive<?= (int)$cfg['id']; ?>" value="1" <?= $cfg['is_active']?'checked':''; ?>>
                            <label class="form-check-label" for="editActive<?= (int)$cfg['id']; ?>"><?= e(t('Set as active config')); ?></label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-check me-1"></i><?= e(t('Update')); ?>
                        </button>
                    </form>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
// ── IDR number formatting ─────────────────────────────────────────
function fmtIdr(v) {
    var n = String(v).replace(/\D/g, '');
    if (!n) return '';
    return n.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function stripIdr(v) { return String(v).replace(/\./g, ''); }
function bindFmtIdr(el) {
    if (el.value) el.value = fmtIdr(el.value);
    el.addEventListener('focus', function() { this.value = stripIdr(this.value); });
    el.addEventListener('blur',  function() { this.value = fmtIdr(this.value); });
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.fmt-idr').forEach(bindFmtIdr);
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            form.querySelectorAll('.fmt-idr').forEach(function(el) {
                el.value = stripIdr(el.value);
            });
        });
    });
});
// ─────────────────────────────────────────────────────────────────

var _isEn = <?= $isEn ? 'true' : 'false'; ?>;
var _baseTypeOpts = [
    {val:'basic_fixed', lbl: _isEn ? 'Base + Fixed Allow.' : 'Gaji Pokok + Tunj. Tetap'},
    {val:'basic_only',  lbl: _isEn ? 'Base Salary only'    : 'Gaji Pokok saja'}
];

function addDedRow(prefix) {
    var container = document.getElementById('ded-rows-' + prefix);
    var row = document.createElement('div');
    row.className = 'ded-row';
    row.style.cssText = 'background:#fff;border:1px solid #fde68a;border-radius:8px;padding:8px';
    var baseOpts = _baseTypeOpts.map(function(o){
        return '<option value="'+o.val+'">'+o.lbl+'</option>';
    }).join('');
    row.innerHTML =
        '<div class="row g-1">'
        +'<div class="col-12"><label style="font-size:11px;color:var(--muted)">'+(_isEn?'Name':'Nama')+'</label>'
        +'<input type="text" name="ded_name[]" class="form-control form-control-sm" required></div>'

        +'<div class="col-6"><label style="font-size:11px;color:var(--muted)">'+(_isEn?'Type':'Tipe')+'</label>'
        +'<select name="ded_type[]" class="form-select form-select-sm" onchange="toggleDedType(this)">'
        +'<option value="percentage">% '+(_isEn?'Percentage':'Persentase')+'</option>'
        +'<option value="fixed">Rp '+(_isEn?'Fixed':'Nominal Tetap')+'</option>'
        +'</select></div>'

        +'<div class="col-6 ded-pct-group"><label style="font-size:11px;color:var(--muted)">Rate (%)</label>'
        +'<div class="input-group input-group-sm"><input type="number" name="ded_rate[]" class="form-control form-control-sm" value="0" min="0" max="100" step="0.01">'
        +'<span class="input-group-text">%</span></div></div>'

        +'<div class="col-6 ded-fixed-group d-none"><label style="font-size:11px;color:var(--muted)">'+(_isEn?'Amount (Rp)':'Jumlah (Rp)')+'</label>'
        +'<input type="text" inputmode="numeric" name="ded_fixed_amount[]" class="form-control form-control-sm fmt-idr"></div>'

        +'<div class="col-12 ded-pct-group"><label style="font-size:11px;color:var(--muted)">'+(_isEn?'Base':'Dasar Perhitungan')+'</label>'
        +'<select name="ded_base_type[]" class="form-select form-select-sm">'+baseOpts+'</select></div>'

        +'<div class="col-12 ded-pct-group"><label style="font-size:11px;color:var(--muted)">'+(_isEn?'Salary Cap (0=no cap)':'Batas Maks Gaji (0=tanpa batas)')+'</label>'
        +'<input type="text" inputmode="numeric" name="ded_base_cap[]" class="form-control form-control-sm fmt-idr"></div>'

        +'<div class="col-12 text-end"><button type="button" class="btn btn-sm btn-outline-danger" style="font-size:11px"'
        +' onclick="this.closest(\'.ded-row\').remove()">'+(_isEn?'Remove':'Hapus')+'</button></div>'
        +'</div>';
    container.appendChild(row);
    row.querySelectorAll('.fmt-idr').forEach(bindFmtIdr);
}

function toggleDedType(sel) {
    var row = sel.closest('.ded-row');
    var isPct = sel.value === 'percentage';
    row.querySelectorAll('.ded-pct-group').forEach(function(el){ el.classList.toggle('d-none', !isPct); });
    row.querySelectorAll('.ded-fixed-group').forEach(function(el){ el.classList.toggle('d-none', isPct); });
}
</script>
