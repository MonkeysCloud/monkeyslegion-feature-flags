<?php
declare(strict_types=1);

namespace MonkeysLegion\FeatureFlags\Drivers;

/**
 * MonkeysLegion Framework — Feature Flags Package
 *
 * In-memory driver for testing and development.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class InMemoryDriver implements FeatureDriverInterface
{
    /** @var array<string, array<string, mixed>> scope => [name => value] */
    private array $flags = [];

    public function get(string $name, mixed $scope = null): mixed
    {
        $scopeKey = $this->scopeKey($scope);
        return $this->flags[$scopeKey][$name] ?? null;
    }

    public function set(string $name, mixed $value, mixed $scope = null): void
    {
        $scopeKey = $this->scopeKey($scope);
        $this->flags[$scopeKey][$name] = $value;
    }

    public function delete(string $name, mixed $scope = null): void
    {
        $scopeKey = $this->scopeKey($scope);
        unset($this->flags[$scopeKey][$name]);
    }

    public function all(mixed $scope = null): array
    {
        $scopeKey = $this->scopeKey($scope);
        return $this->flags[$scopeKey] ?? [];
    }

    private function scopeKey(mixed $scope): string
    {
        if ($scope === null) return '__global__';
        if (is_object($scope)) return spl_object_hash($scope);
        if (is_scalar($scope)) return (string) $scope;
        return md5(serialize($scope));
    }
}
