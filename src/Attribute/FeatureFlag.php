<?php
declare(strict_types=1);

namespace MonkeysLegion\FeatureFlags\Attribute;

use Attribute;

/**
 * MonKeysLegion Framework — Feature Flags Package
 *
 * Route-level feature flag attribute.
 *
 * Usage:
 *   #[FeatureFlag('new-api')]
 *   #[FeatureFlag('beta-features', redirect: '/beta-required')]
 *
 * @copyright 2026 MonKeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class FeatureFlag
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $redirect = null,
        public readonly ?string $errorMessage = null,
    ) {}
}
