<section class="menu-grid-wrap mt-3">
    <div class="row g-3 row-cols-3">
        <div class="col">
            <a class="menu-card" href="<?= e(base_url('/transactions/add')); ?>">
                <div class="menu-icon-wrap"><i class="fa-solid fa-plus-circle"></i></div>
                <small><?= e(t('Add Expense')); ?></small>
            </a>
        </div>
        <div class="col">
            <a class="menu-card" href="<?= e(base_url('/bills')); ?>">
                <div class="menu-icon-wrap"><i class="fa-solid fa-rotate"></i></div>
                <small><?= e(t('Bills')); ?></small>
            </a>
        </div>
        <div class="col">
            <a class="menu-card" href="<?= e(base_url('/projection')); ?>">
                <div class="menu-icon-wrap"><i class="fa-solid fa-chart-line"></i></div>
                <small><?= e(t('Projection')); ?></small>
            </a>
        </div>
        <div class="col">
            <a class="menu-card" href="<?= e(base_url('/reports')); ?>">
                <div class="menu-icon-wrap"><i class="fa-solid fa-chart-pie"></i></div>
                <small><?= e(t('Reports')); ?></small>
            </a>
        </div>
        <div class="col">
            <a class="menu-card" href="<?= e(base_url('/budget')); ?>">
                <div class="menu-icon-wrap"><i class="fa-solid fa-bullseye"></i></div>
                <small><?= e(t('Budget')); ?></small>
            </a>
        </div>
        <div class="col">
            <a class="menu-card" href="<?= e(base_url('/income')); ?>">
                <div class="menu-icon-wrap"><i class="fa-solid fa-receipt"></i></div>
                <small><?= e(t('Income')); ?></small>
            </a>
        </div>
        <div class="col">
            <a class="menu-card" href="<?= e(base_url('/categories')); ?>">
                <div class="menu-icon-wrap"><i class="fa-solid fa-layer-group"></i></div>
                <small><?= e(t('Categories')); ?></small>
            </a>
        </div>
        <div class="col">
            <a class="menu-card" href="<?= e(base_url('/salary-config')); ?>">
                <div class="menu-icon-wrap"><i class="fa-solid fa-building"></i></div>
                <small><?= e(t('Salary')); ?></small>
            </a>
        </div>
    </div>
</section>
