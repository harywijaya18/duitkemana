<?php

class ApiRateLimiter
{
    public function check(string $key, int $maxRequests, int $windowSeconds): array
    {
        $maxRequests = max(1, $maxRequests);
        $windowSeconds = max(1, $windowSeconds);

        $now = time();
        $bucket = $this->loadBucket($key);

        if (($bucket['window_start'] ?? 0) <= 0 || ($now - (int) $bucket['window_start']) >= $windowSeconds) {
            $bucket = [
                'window_start' => $now,
                'count' => 0,
            ];
        }

        $count = (int) ($bucket['count'] ?? 0);
        $allowed = $count < $maxRequests;

        if ($allowed) {
            $count++;
            $bucket['count'] = $count;
            $this->saveBucket($key, $bucket);
        }

        $remaining = max(0, $maxRequests - $count);
        $resetAt = (int) $bucket['window_start'] + $windowSeconds;
        $retryAfter = max(0, $resetAt - $now);

        return [
            'allowed' => $allowed,
            'limit' => $maxRequests,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
            'retry_after' => $retryAfter,
        ];
    }

    private function storageDir(): string
    {
        $dir = BASE_PATH . '/storage/rate_limits';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function bucketPath(string $key): string
    {
        return $this->storageDir() . '/' . sha1($key) . '.json';
    }

    private function loadBucket(string $key): array
    {
        $path = $this->bucketPath($key);
        if (!file_exists($path)) {
            return ['window_start' => 0, 'count' => 0];
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return ['window_start' => 0, 'count' => 0];
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return ['window_start' => 0, 'count' => 0];
        }

        return [
            'window_start' => (int) ($json['window_start'] ?? 0),
            'count' => (int) ($json['count'] ?? 0),
        ];
    }

    private function saveBucket(string $key, array $bucket): void
    {
        $path = $this->bucketPath($key);
        file_put_contents($path, json_encode([
            'window_start' => (int) ($bucket['window_start'] ?? 0),
            'count' => (int) ($bucket['count'] ?? 0),
        ]));
    }
}
