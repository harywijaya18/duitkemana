<section class="mb-3">
    <h4><?= e(t('Categories')); ?></h4>
</section>

<div class="soft-card mb-3">
    <form method="post" action="<?= e(base_url('/categories/store')); ?>" class="row g-2 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <div class="col-5">
            <label class="form-label"><?= e(t('Name')); ?></label>
            <input type="text" name="name" class="form-control" placeholder="<?= e(t('New category')); ?>" required>
        </div>
        <div class="col-4">
            <label class="form-label"><?= e(t('Icon')); ?></label>
            <input type="text" name="icon" class="form-control" placeholder="fa-wallet">
        </div>
        <div class="col-3 d-grid">
            <button class="btn btn-primary" type="submit"><?= e(t('Add')); ?></button>
        </div>
    </form>
</div>

<div class="vstack gap-2">
    <?php foreach ($categories as $category): ?>
        <div class="soft-card row g-2 align-items-center">
            <form method="post" action="<?= e(base_url('/categories/update')); ?>" class="col-9 row g-2 align-items-center">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?= (int) $category['id']; ?>">
                <div class="col-2 text-center">
                    <i class="fa-solid <?= e($category['icon']); ?>"></i>
                </div>
                <div class="col-6">
                    <input type="text" name="name" class="form-control" value="<?= e($category['name']); ?>" required>
                </div>
                <div class="col-4">
                    <input type="text" name="icon" class="form-control" value="<?= e($category['icon']); ?>">
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-light" type="submit"><i class="fa-solid fa-check me-1"></i><?= e(t('Update')); ?></button>
                </div>
            </form>
            <form method="post" action="<?= e(base_url('/categories/delete')); ?>" class="col-3 d-grid" onsubmit="return confirm('<?= e(t('Delete category?')); ?>');">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?= (int) $category['id']; ?>">
                <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
            </form>
            </div>
    <?php endforeach; ?>
</div>
