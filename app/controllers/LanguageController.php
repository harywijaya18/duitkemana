<?php

class LanguageController extends Controller
{
    public function switch(): void
    {
        if (!verify_csrf()) {
            redirect('/');
        }

        $lang = trim($_POST['lang'] ?? '');
        set_language($lang);

        $redirect = trim($_POST['redirect'] ?? '/');
        if ($redirect === '' || str_starts_with($redirect, 'http://') || str_starts_with($redirect, 'https://')) {
            $redirect = '/';
        }

        if (!str_starts_with($redirect, '/')) {
            $redirect = '/' . ltrim($redirect, '/');
        }

        redirect($redirect);
    }
}
