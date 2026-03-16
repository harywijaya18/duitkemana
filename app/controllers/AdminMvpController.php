<?php

class AdminMvpController extends Controller
{
    public function users(): void
    {
        $this->renderMvpPage('users', 'User Management', 'Kelola akun user, status, dan lifecycle operasional.');
    }

    public function subscriptions(): void
    {
        $this->renderMvpPage('subscriptions', 'Subscription & Billing', 'Pantau paket, status langganan, invoice, dan pembayaran.');
    }

    public function operations(): void
    {
        $this->renderMvpPage('operations', 'Operations Monitor', 'Pantau health scheduler, antrian proses, dan error operasional.');
    }

    public function analytics(): void
    {
        $this->renderMvpPage('analytics', 'Product Analytics', 'Analisis adopsi fitur, retention, dan funnel onboarding.');
    }

    public function support(): void
    {
        $this->renderMvpPage('support', 'Support Center', 'Kelola tiket, feedback, dan komunikasi ke user.');
    }

    public function settings(): void
    {
        $this->renderMvpPage('settings', 'Admin Settings', 'Konfigurasi global, feature flags, dan kontrol keamanan admin.');
    }

    private function renderMvpPage(string $activeMenu, string $title, string $description): void
    {
        require_admin();

        $user = auth_user();
        $viewFile = APP_PATH . '/views/admin/mvp_section.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }
}
