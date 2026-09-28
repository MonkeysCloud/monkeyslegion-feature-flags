# MonKeysLegion Feature Flags

Pennant-style feature flag system for the MonKeysLegion framework.

## Features

- **Boolean flags** — simple on/off
- **Percentage rollout** — deterministic per-scope (CRC32 hashing)
- **A/B testing** — weighted variant distribution
- **Per-scope evaluation** — user, team, global
- **Route-level gating** — `#[FeatureFlag]` attribute + middleware
- **Multiple drivers** — InMemory, Database (PDO), Redis

## Installation

```bash
composer require monkeyscloud/monkeyslegion-feature-flags
```

## Usage

```php
use MonkeysLegion\FeatureFlags\Feature;

// Define flags
Feature::defineBool('new-ui', false);
Feature::definePercentage('beta-access', 25);
Feature::defineAB('button-color', ['red' => 70, 'blue' => 30]);

// Check flags
if (Feature::isActive('new-ui')) { /* ... */ }
if (Feature::for($user)->isActive('beta-access')) { /* ... */ }
$variant = Feature::value('button-color', $user);
```

## License

MIT © MonKeysCloud
