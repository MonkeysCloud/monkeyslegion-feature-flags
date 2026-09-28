<?php
declare(strict_types=1);

namespace MonkeysLegion\FeatureFlags;

/**
 * MonKeysLegion Framework — Feature Flags Package
 *
 * Static facade for the FeatureManager.
 *
 * Usage:
 *   Feature::defineBool('new-ui', false);
 *   Feature::for($user)->isActive('new-ui');
 *   Feature::definePercentage('beta-access', 25);
 *
 * @copyright 2026 MonKeysCloud Team
 * @license   MIT
 */
final class Feature
{
    private static ?FeatureManager $instance = null;

    /**
     * Set the global FeatureManager instance.
     */
    public static function setInstance(FeatureManager $manager): void
    {
        self::$instance = $manager;
    }

    /**
     * Get the global FeatureManager instance.
     */
    public static function instance(): FeatureManager
    {
        if (self::$instance === null) {
            throw new \RuntimeException('FeatureManager not initialized. Call Feature::setInstance() first.');
        }
        return self::$instance;
    }

    /**
     * Define a feature flag with a custom resolver.
     */
    public static function define(string $name, callable $resolver): void
    {
        self::instance()->define($name, $resolver);
    }

    /**
     * Define a boolean flag.
     */
    public static function defineBool(string $name, bool $default = false): void
    {
        self::instance()->defineBool($name, $default);
    }

    /**
     * Define a percentage rollout flag.
     */
    public static function definePercentage(string $name, int $percent): void
    {
        self::instance()->definePercentage($name, $percent);
    }

    /**
     * Define an A/B test.
     *
     * @param array<string, int> $variants
     */
    public static function defineAB(string $name, array $variants): void
    {
        self::instance()->defineAB($name, $variants);
    }

    /**
     * Check if a flag is active.
     */
    public static function isActive(string $name, mixed $scope = null): bool
    {
        return self::instance()->isActive($name, $scope);
    }

    /**
     * Get the raw flag value.
     */
    public static function value(string $name, mixed $scope = null): mixed
    {
        return self::instance()->value($name, $scope);
    }

    /**
     * Override a flag for a specific scope.
     */
    public static function set(string $name, mixed $value, mixed $scope = null): void
    {
        self::instance()->set($name, $value, $scope);
    }

    /**
     * Set the default scope for subsequent calls.
     */
    public static function for(mixed $scope): FeatureManager
    {
        return self::instance()->forScope($scope);
    }
}
