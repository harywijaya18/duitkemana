<?php
$activeMenu = $activeMenu ?? 'dashboard';
$title = $title ?? 'MVP Section';
$description = $description ?? 'Section description';
?>

<section class="admin-hero-card mb-4">
    <div>
        <h1 class="mb-1"><?= e($title); ?></h1>
        <p class="mb-0"><?= e($description); ?></p>
    </div>
    <div class="admin-hero-meta">
        <span><i class="fa-solid fa-screwdriver-wrench me-1"></i>Blueprint Mode</span>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h5 class="mb-0">Scope MVP</h5>
        <span class="badge text-bg-warning">Planned</span>
    </div>
    <div class="admin-mvp-grid">
        <div>
            <h6>Goals</h6>
            <ul>
                <li>Memberikan visibilitas operasional untuk admin SaaS.</li>
                <li>Mengurangi pekerjaan manual monitoring dan support.</li>
                <li>Menyiapkan fondasi modul sebelum automation lanjutan.</li>
            </ul>
        </div>
        <div>
            <h6>Initial Deliverables</h6>
            <ul>
                <li>Tabel data utama + filter periode.</li>
                <li>Aksi admin dasar sesuai modul.</li>
                <li>Audit log untuk aksi sensitif.</li>
                <li>Ekspor CSV untuk kebutuhan operasional.</li>
            </ul>
        </div>
    </div>
</section>
