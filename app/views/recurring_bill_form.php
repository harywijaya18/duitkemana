<?php
$isEdit = ($bill !== null);

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

$selStartYear  = $isEdit ? (int) $bill['start_year']        : (int) date('Y');
$selStartMonth = $isEdit ? (int) $bill['start_month']       : (int) date('n');
$selDuration   = $isEdit ? (int) ($bill['duration_months']  ?? 0) : 0;
$selEndYear    = $isEdit ? (int) ($bill['end_year']         ?? 0) : 0;
$selEndMonth   = $isEdit ? (int) ($bill['end_month']        ?? 0) : 0;

if ($isEdit) {
    if ($bill['duration_months'] > 0) {
        $selEndType = 'duration';
    } elseif ($bill['end_year'] && $bill['end_month']) {
        $selEndType = 'end_date';
    } else {
        $selEndType = 'indefinite';
    }
} else {
    $selEndType = 'duration';
}
?>

<section class="mb-3">
    <h4 class="mb-0">
        <i class="fa-solid fa-rotate me-1 text-primary"></i>
        <?= e($isEdit ? t('Edit Bill') : t('Add Bill')); ?>
    </h4>
</section>

<div class="soft-card">
    <form method="post"
          action="<?= e($isEdit ? base_url('/bills/update') : base_url('/bills/store')); ?>"
          class="vstack gap-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$bill['id']; ?>">
        <?php endif; ?>

        <!-- ─── Bill name ─── -->
        <div>
            <label class="form-label"><?= e(t('Bill Name')); ?></label>
            <input type="text" name="name" class="form-control"
                   value="<?= e($isEdit ? $bill['name'] : ''); ?>"
                   placeholder="<?= e(t('e.g. Cicilan Motor, Cicilan Rumah, Netflix')); ?>" required>
        </div>

        <!-- ─── Amount per month ─── -->
        <div>
            <label class="form-label"><?= e(t('Amount / Month')); ?></label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                  <input type="text" inputmode="numeric" name="amount" id="billAmount" class="form-control fmt-idr"
                      value="<?= $isEdit ? number_format((float)$bill['amount'], 0, ',', '.') : ''; ?>"
                      placeholder="0" required>
            </div>
        </div>

        <!-- ─── Category (optional) ─── -->
        <div>
            <label class="form-label">
                <?= e(t('Category')); ?>
                <span class="text-muted" style="font-weight:400">(<?= e(t('optional')); ?>)</span>
            </label>
            <select name="category_id" class="form-select">
                <option value="">— <?= e(t('None')); ?> —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id']; ?>"
                        <?= ($isEdit && (int)$bill['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                        <?= e($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ─── Start month / year ─── -->
        <div>
            <label class="form-label"><?= e(t('Start Month')); ?></label>
            <div class="row g-2">
                <div class="col-7">
                    <select name="start_month" id="startMonth" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m; ?>" <?= $selStartMonth === $m ? 'selected' : ''; ?>>
                                <?= e($mNames[$m]); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-5">
                    <input type="number" name="start_year" id="startYear" class="form-control"
                           value="<?= $selStartYear; ?>" min="2000" max="2099" required>
                </div>
            </div>
        </div>

        <!-- ─── Duration / End type ─── -->
        <div>
            <label class="form-label"><?= e(t('Duration / End')); ?></label>
            <div class="vstack gap-2 ps-1">

                <!-- Option A: duration in months -->
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="end_type" id="endTypeDuration"
                           value="duration" <?= $selEndType === 'duration' ? 'checked' : ''; ?>
                           onchange="switchEndType()">
                    <label class="form-check-label" for="endTypeDuration">
                        <?= e(t('Duration (number of months)')); ?>
                    </label>
                </div>
                <div id="durationSection" class="ms-3 <?= $selEndType !== 'duration' ? 'd-none' : ''; ?>">
                    <div class="input-group" style="max-width:220px">
                        <input type="number" name="duration_months" id="durationMonths"
                               class="form-control" min="1" max="600"
                               value="<?= $selDuration ?: ''; ?>"
                               placeholder="<?= e(t('e.g. 36')); ?>"
                               oninput="updateEndPreview()">
                        <span class="input-group-text"><?= e(t('months')); ?></span>
                    </div>
                    <div id="endDatePreview" class="text-muted mt-1" style="font-size:12px"></div>
                </div>

                <!-- Option B: specific end month -->
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="end_type" id="endTypeEndDate"
                           value="end_date" <?= $selEndType === 'end_date' ? 'checked' : ''; ?>
                           onchange="switchEndType()">
                    <label class="form-check-label" for="endTypeEndDate">
                        <?= e(t('Specific end month')); ?>
                    </label>
                </div>
                <div id="endDateSection" class="ms-3 row g-2 <?= $selEndType !== 'end_date' ? 'd-none' : ''; ?>" style="max-width:280px">
                    <div class="col-7">
                        <select name="end_month" id="endMonth" class="form-select">
                            <option value="">—</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m; ?>" <?= $selEndMonth === $m ? 'selected' : ''; ?>>
                                    <?= e($mNames[$m]); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <input type="number" name="end_year" id="endYear" class="form-control"
                               value="<?= $selEndYear ?: ''; ?>"
                               min="2000" max="2099"
                               placeholder="<?= date('Y') + 2; ?>">
                    </div>
                </div>

                <!-- Option C: indefinite -->
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="end_type" id="endTypeIndefinite"
                           value="indefinite" <?= $selEndType === 'indefinite' ? 'checked' : ''; ?>
                           onchange="switchEndType()">
                    <label class="form-check-label" for="endTypeIndefinite">
                        <?= e(t('Indefinite (ongoing)')); ?>
                    </label>
                </div>

            </div>
        </div>

        <!-- ─── Notes ─── -->
        <div>
            <label class="form-label">
                <?= e(t('Notes')); ?>
                <span class="text-muted" style="font-weight:400">(<?= e(t('optional')); ?>)</span>
            </label>
            <input type="text" name="notes" class="form-control"
                   value="<?= e($isEdit ? ($bill['notes'] ?? '') : ''); ?>"
                   placeholder="<?= e(t('e.g. Bank BCA, due date 15')); ?>">
        </div>

        <!-- ─── Active toggle (edit only) ─── -->
        <?php if ($isEdit): ?>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                       value="1" <?= ($bill['is_active'] ?? 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="isActive"><?= e(t('Active')); ?></label>
            </div>
        <?php endif; ?>

        <!-- ─── Submit ─── -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="fa-solid fa-floppy-disk me-1"></i>
                <?= e($isEdit ? t('Update Bill') : t('Save Bill')); ?>
            </button>
            <a href="<?= e(base_url('/bills')); ?>" class="btn btn-outline-secondary flex-fill">
                <?= e(t('Cancel')); ?>
            </a>
        </div>
    </form>
</div>

<script>
// Month names array for JS end-date preview
const _mNames = <?= json_encode(array_values($mNames), JSON_UNESCAPED_UNICODE); ?>;

function switchEndType() {
    const type = document.querySelector('input[name="end_type"]:checked')?.value ?? 'indefinite';
    document.getElementById('durationSection').classList.toggle('d-none', type !== 'duration');
    document.getElementById('endDateSection').classList.toggle('d-none', type !== 'end_date');
    if (type !== 'duration') {
        document.getElementById('durationMonths').value = '';
        document.getElementById('endDatePreview').textContent = '';
    }
    if (type !== 'end_date') {
        document.getElementById('endMonth').value = '';
        document.getElementById('endYear').value  = '';
    }
}

function updateEndPreview() {
    const dur        = parseInt(document.getElementById('durationMonths').value) || 0;
    const startYear  = parseInt(document.getElementById('startYear').value)  || <?= $selStartYear; ?>;
    const startMonth = parseInt(document.getElementById('startMonth').value) || <?= $selStartMonth; ?>;
    const preview    = document.getElementById('endDatePreview');

    if (dur <= 0) { preview.textContent = ''; return; }

    const end = new Date(startYear, startMonth - 1 + dur - 1, 1);
    preview.textContent = '→ ' + (_mNames[end.getMonth()] ?? '') + ' ' + end.getFullYear();
}

// Wire start month/year changes to update preview
document.getElementById('startMonth')?.addEventListener('change', updateEndPreview);
document.getElementById('startYear')?.addEventListener('input',  updateEndPreview);

<?php if ($isEdit && $selDuration > 0): ?>
updateEndPreview();
<?php endif; ?>

// ── IDR formatting ──────────────────────────────────────
function fmtIdr(n) { return n === '' ? '' : Number(n).toLocaleString('id-ID', {maximumFractionDigits:0}); }
function stripIdr(v) { return String(v).replace(/\./g,'').replace(/[^0-9]/g,''); }
function bindFmtIdr(el) {
    if (!el) return;
    el.addEventListener('focus', () => { el.value = stripIdr(el.value); });
    el.addEventListener('blur',  () => { const n = stripIdr(el.value); el.value = n ? fmtIdr(n) : ''; });
    if (el.value) el.value = fmtIdr(stripIdr(el.value));
}
document.querySelectorAll('.fmt-idr').forEach(bindFmtIdr);
document.querySelector('form')?.addEventListener('submit', function() {
    this.querySelectorAll('.fmt-idr').forEach(el => { el.value = stripIdr(el.value); });
});
</script>
