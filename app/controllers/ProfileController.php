<?php

class ProfileController extends Controller
{
    private UserModel $userModel;
    private SupportTicketModel $supportTicketModel;

    public function __construct()
    {
        $this->userModel = $this->model(UserModel::class);
        $this->supportTicketModel = $this->model(SupportTicketModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user = auth_user();
        $profile = $this->userModel->findById((int) $user['id']);
        $supportEnabled = $this->supportTicketModel->isEnabled();

        $this->view('profile', [
            'profile' => $profile,
            'supportEnabled' => $supportEnabled,
        ]);
    }

    public function supportCenter(): void
    {
        require_auth();
        $user = auth_user();
        $ticketPage = max(1, (int) ($_GET['ticket_page'] ?? 1));
        $supportTickets = $this->supportTicketModel->paginateForUser((int) $user['id'], $ticketPage, 10);
        $supportEnabled = $this->supportTicketModel->isEnabled();

        $this->view('support_center', [
            'supportTickets' => $supportTickets,
            'supportEnabled' => $supportEnabled,
        ]);
    }

    public function aboutApp(): void
    {
        require_auth();

        $this->view('about_app', [
            'appVersion' => 'v1.0',
        ]);
    }

    public function update(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/profile');
        }

        $user = auth_user();
        $name = trim($_POST['name'] ?? '');
        $currency = trim($_POST['currency'] ?? 'IDR');

        if ($name === '') {
            flash('error', t('Name is required.'));
            redirect('/profile');
        }

        $this->userModel->updateProfile((int) $user['id'], $name, $currency ?: 'IDR');
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['currency'] = $currency ?: 'IDR';

        flash('success', t('Profile updated.'));
        redirect('/profile');
    }

    public function submitSupportTicket(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/profile/support-center');
        }

        if (!$this->supportTicketModel->isEnabled()) {
            flash('error', t('Support Center is currently unavailable.'));
            redirect('/profile/support-center');
        }

        $user = auth_user();
        $category = trim((string) ($_POST['category'] ?? 'general'));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $priority = trim((string) ($_POST['priority'] ?? 'normal'));
        $initialMessage = trim((string) ($_POST['initial_message'] ?? ''));

        if (!in_array($category, ['general', 'billing', 'technical', 'feature_request', 'account'], true)) {
            $category = 'general';
        }
        if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            $priority = 'normal';
        }
        if ($subject === '') {
            flash('error', t('Subject is required.'));
            redirect('/profile/support-center');
        }
        if ($initialMessage === '') {
            flash('error', t('Message is required.'));
            redirect('/profile/support-center');
        }

        $created = $this->supportTicketModel->createTicket((int) $user['id'], $category, $subject, $priority, $initialMessage);
        if (!$created) {
            flash('error', t('Failed to send support ticket.'));
            redirect('/profile/support-center');
        }

        flash('success', t('Support ticket sent successfully.'));
        redirect('/profile/support-center');
    }
}
