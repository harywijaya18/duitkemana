    </main>

    <?php if ($user): ?>
        <?php require APP_PATH . '/views/components/bottom_nav.php'; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= e(base_url('/assets/js/app.js')); ?>?v=<?= filemtime(BASE_PATH . '/public/assets/js/app.js'); ?>"></script>
</body>
</html>
