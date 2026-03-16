<?php

class NotificationController extends Controller
{
    private NotificationModel $notifModel;

    public function __construct()
    {
        $this->notifModel = $this->model(NotificationModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user = auth_user();

        // Generate fresh notifications before showing the list
        $this->notifModel->generateForUser($user['id'], (int) date('n'), (int) date('Y'));

        $notifications = $this->notifModel->getAll($user['id']);

        $this->view('notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/notifications');
        }
        $user = auth_user();
        $id   = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $this->notifModel->markRead($user['id'], $id);
        // Update session badge count
        $_SESSION['notif_count'] = $this->notifModel->countUnread($user['id']);
        redirect('/notifications');
    }
}
