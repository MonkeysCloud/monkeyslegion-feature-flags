<?php
declare(strict_types=1);

namespace MonkeysLegion\FeatureFlags\Providers;

use MonkeysLegion\FeatureFlags\Drivers\DatabaseDriver;
use MonkeysLegion\FeatureFlags\Drivers\FeatureDriverInterface;
use MonkeysLegion\FeatureFlags\Drivers\InMemoryDriver;
use MonkeysLegion\FeatureFlags\Drivers\RedisDriver;
use MonKeysLegion\FeatureFlags\Feature;
use MonkeysLegion\FeatureFlags\FeatureManager;

/**
 * MonKeysLegion Framework — Feature Flags Package
 *
 * Service provider that registers the feature flag driver and manager.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class FeatureFlagServiceProvider
{
    /**
     * Register feature flag services.
     *
     * @param array<string, mixed> $config Configuration from feature-flags.mlc
     * @param callable(string, callable): void $register DI registration callback
     * @param callable(string): object $resolve DI resolution callback
     */
    public function register(array $config, callable $register, callable $resolve): void
    {
        $driver = $config['driver'] ?? 'memory';

        $register(FeatureDriverInterface::class, function() use ($driver, $config, $resolve): FeatureDriverInterface {
            return match ($driver) {
                'database' => new DatabaseDriver(
                    $resolve('db'),
                    $config['table'] ?? 'feature_flags',
                ),
                'redis' => new RedisDriver(
                    $resolve('redis'),
                    $config['prefix'] ?? 'ml:feature_flags:',
                ),
                'memory', 'array' => new InMemoryDriver(),
                default => new InMemoryDriver(),
            };
        });

        $register(FeatureManager::class, function() use ($resolve): FeatureManager {
            return new FeatureManager($resolve(FeatureDriverInterface::class));
        });

        // Initialize the static facade
        $manager = $resolve(FeatureManager::class);
        Feature::setInstance($manager);
    }
}
