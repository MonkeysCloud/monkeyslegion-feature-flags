<?php
declare(strict_types=1);

namespace MonKeysLegion\FeatureFlags\Drivers;

use Redis;
use RedisException;

/**
 * MonKeysLegion Framework — Feature Flags Package
 *
 * Redis driver storing flags with namespaced keys.
 *
 * @copyright 2026 MonKeysCloud Team
 * @license   MIT
 */
final class RedisDriver implements FeatureDriverInterface
{
    private readonly string $prefix;

    public function __construct(
        private readonly Redis $redis,
        string $prefix = 'ml:feature_flags:',
    ) {
        $this->prefix = $prefix;
    }

    public function get(string $name, mixed $scope = null): mixed
    {
        $key = $this->buildKey($name, $scope);
        try {
            $value = $this->redis->get($key);
            if ($value === false) return null;
            $decoded = json_decode($value, true);
            return $decoded === null ? $value : $decoded;
        } catch (RedisException) {
            return null;
        }
    }

    public function set(string $name, mixed $value, mixed $scope = null): void
    {
        $key = $this->buildKey($name, $scope);
        $this->redis->set($key, json_encode($value, JSON_THROW_ON_ERROR));
    }

    public function delete(string $name, mixed $scope = null): void
    {
        $key = $this->buildKey($name, $scope);
        $this->redis->del($key);
    }

    public function all(mixed $scope = null): array
    {
        $pattern = $this->prefix . $this->scopeKey($scope) . ':*';
        try {
            $keys = $this->redis->keys($pattern);
            $flags = [];
            foreach ($keys as $key) {
                // Extract flag name from key: prefix:scope:name
                $parts = explode(':', $key);
                $name = end($parts);
                $value = $this->redis->get($key);
                if ($value !== false) {
                    $decoded = json_decode($value, true);
                    $flags[$name] = $decoded === null ? $value : $decoded;
                }
            }
            return $flags;
        } catch (RedisException) {
            return [];
        }
    }

    private function buildKey(string $name, mixed $scope): string
    {
        return $this->prefix . $this->scopeKey($scope) . ':' . $name;
    }

    private function scopeKey(mixed $scope): string
    {
        if ($scope === null) return '__global__';
        if (is_object($scope)) return spl_object_hash($scope);
        if (is_scalar($scope)) return (string) $scope;
        return md5(serialize($scope));
    }
}
