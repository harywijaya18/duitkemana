<?php

function api_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function api_bearer_token(): ?string
{
    $auth = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? $_SERVER['Authorization']
        ?? $_SERVER['HTTP_X_AUTHORIZATION']
        ?? '';

    if (!$auth && function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            $auth = $headers['Authorization']
                ?? $headers['authorization']
                ?? $headers['X-Authorization']
                ?? $headers['x-authorization']
                ?? '';
        }
    }

    if (!$auth && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (is_array($headers)) {
            $auth = $headers['Authorization']
                ?? $headers['authorization']
                ?? $headers['X-Authorization']
                ?? $headers['x-authorization']
                ?? '';
        }
    }

    if (!$auth) {
        return null;
    }

    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return trim($matches[1]);
    }

    return null;
}
