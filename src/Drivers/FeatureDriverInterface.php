<?php
declare(strict_types=1);

namespace MonkeysLegion\FeatureFlags\Drivers;

/**
 * MonkeysLegion Framework — Feature Flags Package
 *
 * Contract for feature flag storage drivers.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
interface FeatureDriverInterface
{
    /**
     * Get the value of a feature flag for a given scope.
     *
     * @param string $name  Flag name.
     * @param mixed  $scope Scope identifier (user ID, team ID, null = global).
     *
     * @return mixed The flag value (bool, string, int, or null if not defined).
     */
    public function get(string $name, mixed $scope = null): mixed;

    /**
     * Set the value of a feature flag for a given scope.
     *
     * @param string $name   Flag name.
     * @param mixed  $value  Flag value.
     * @param mixed  $scope  Scope identifier.
     */
    public function set(string $name, mixed $value, mixed $scope = null): void;

    /**
     * Delete a feature flag for a given scope.
     *
     * @param string $name  Flag name.
     * @param mixed  $scope Scope identifier.
     */
    public function delete(string $name, mixed $scope = null): void;

    /**
     * Get all defined feature flags for a given scope.
     *
     * @param mixed $scope Scope identifier.
     *
     * @return array<string, mixed>
     */
    public function all(mixed $scope = null): array;
}
