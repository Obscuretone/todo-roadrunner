<?php
/**
 * Stub for the Redis extension to help Intelephense recognize it.
 */
class Redis {
    public function connect(string $host, int $port, float $timeout = 0.0): bool { return true; }
    public function auth(string $password): bool { return true; }
    public function set(string $key, string $value): bool { return true; }
    public function get(string $key): string|false { return false; }
    public function exists(string ...$keys): int { return 0; }
    public function del(string ...$keys): int {return 0; }
}