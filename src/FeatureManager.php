<?php
declare(strict_types=1);

namespace MonkeysLegion\FeatureFlags;

use MonkeysLegion\FeatureFlags\Drivers\FeatureDriverInterface;

/**
 * MonkeysLegion Framework — Feature Flags Package
 *
 * Central manager for defining, resolving, and evaluating feature flags.
 *
 * Supports:
 *   • Boolean flags (on/off)
 *   • Percentage rollout (deterministic per scope)
 *   • A/B variants
 *   • Per-scope evaluation (user, team, global)
 *
 * @copyright 2026 MonKeysCloud Team
 * @license   MIT
 */
final class FeatureManager
{
    /** @var array<string, callable(mixed): mixed> name => resolver */
    private array $definitions = [];

    private mixed $defaultScope = null;

    public function __construct(
        private readonly FeatureDriverInterface $driver,
    ) {}

    /**
     * Define a feature flag with a resolver callback.
     *
     * @param string $name     Flag name.
     * @param callable(mixed): mixed $resolver Returns the flag value for a scope.
     */
    public function define(string $name, callable $resolver): void
    {
        $this->definitions[$name] = $resolver;
    }

    /**
     * Define a boolean flag (simple on/off).
     */
    public function defineBool(string $name, bool $default = false): void
    {
        $this->define($name, fn(mixed $scope): bool => $this->driver->get($name, $scope) ?? $default);
    }

    /**
     * Define a percentage rollout flag.
     *
     * Uses deterministic hashing so the same scope always gets the same result.
     *
     * @param string $name     Flag name.
     * @param int    $percent  Percentage of scopes that should be active (0-100).
     */
    public function definePercentage(string $name, int $percent): void
    {
        $this->define($name, function(mixed $scope) use ($percent, $name): bool {
            // Check explicit override first
            $stored = $this->driver->get($name, $scope);
            if ($stored !== null) return (bool) $stored;

            if ($percent <= 0) return false;
            if ($percent >= 100) return true;

            $scopeId = $this->scopeId($scope);
            $hash = crc32($name . ':' . $scopeId);
            $bucket = abs($hash) % 100;
            return $bucket < $percent;
        });
    }

    /**
     * Define an A/B test with named variants.
     *
     * @param string $name       Flag name.
     * @param array<string, int> $variants variant => weight.
     */
    public function defineAB(string $name, array $variants): void
    {
        $this->define($name, function(mixed $scope) use ($variants, $name): string {
            $stored = $this->driver->get($name, $scope);
            if ($stored !== null && is_string($stored)) return $stored;

            $scopeId = $this->scopeId($scope);
            $hash = abs(crc32($name . ':' . $scopeId));
            $totalWeight = array_sum($variants);
            $bucket = $hash % $totalWeight;

            $cumulative = 0;
            foreach ($variants as $variant => $weight) {
                $cumulative += $weight;
                if ($bucket < $cumulative) return $variant;
            }

            return array_key_first($variants);
        });
    }

    /**
     * Check if a feature flag is active (boolean).
     */
    public function isActive(string $name, mixed $scope = null): bool
    {
        $scope = $scope ?? $this->defaultScope;
        return (bool) $this->resolve($name, $scope);
    }

    /**
     * Get the raw value of a feature flag.
     */
    public function value(string $name, mixed $scope = null): mixed
    {
        $scope = $scope ?? $this->defaultScope;
        return $this->resolve($name, $scope);
    }

    /**
     * Override a flag value for a specific scope.
     */
    public function set(string $name, mixed $value, mixed $scope = null): void
    {
        $this->driver->set($name, $value, $scope ?? $this->defaultScope);
    }

    /**
     * Remove a flag override for a specific scope.
     */
    public function delete(string $name, mixed $scope = null): void
    {
        $this->driver->delete($name, $scope ?? $this->defaultScope);
    }

    /**
     * Set the default scope for subsequent operations.
     */
    public function forScope(mixed $scope): self
    {
        $this->defaultScope = $scope;
        return $this;
    }

    /**
     * Get all defined flag names.
     *
     * @return list<string>
     */
    public function definedFlags(): array
    {
        return array_keys($this->definitions);
    }

    /**
     * Resolve a flag through its definition.
     */
    private function resolve(string $name, mixed $scope): mixed
    {
        if (!isset($this->definitions[$name])) {
            return $this->driver->get($name, $scope);
        }

        return ($this->definitions[$name])($scope);
    }

    /**
     * Extract a stable string ID from a scope.
     */
    private function scopeId(mixed $scope): string
    {
        if ($scope === null) return '__global__';
        if (is_object($scope)) {
            if (method_exists($scope, 'getAuthIdentifier')) return (string) $scope->getAuthIdentifier();
            if (property_exists($scope, 'id')) return (string) $scope->id;
            return spl_object_hash($scope);
        }
        if (is_scalar($scope)) return (string) $scope;
        return md5(serialize($scope));
    }
}
