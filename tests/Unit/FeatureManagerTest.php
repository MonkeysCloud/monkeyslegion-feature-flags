<?php
declare(strict_types=1);

namespace MonkeysLegion\FeatureFlags\Tests\Unit;

use MonkeysLegion\FeatureFlags\Drivers\InMemoryDriver;
use MonkeysLegion\FeatureFlags\FeatureManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the FeatureManager.
 */
final class FeatureManagerTest extends TestCase
{
    private FeatureManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new FeatureManager(new InMemoryDriver());
    }

    #[Test]
    public function define_bool_flag_defaults_false(): void
    {
        $this->manager->defineBool('new-ui', false);
        self::assertFalse($this->manager->isActive('new-ui'));
    }

    #[Test]
    public function define_bool_flag_defaults_true(): void
    {
        $this->manager->defineBool('new-ui', true);
        self::assertTrue($this->manager->isActive('new-ui'));
    }

    #[Test]
    public function override_bool_flag(): void
    {
        $this->manager->defineBool('new-ui', false);
        $this->manager->set('new-ui', true);
        self::assertTrue($this->manager->isActive('new-ui'));
    }

    #[Test]
    public function override_flag_for_specific_scope(): void
    {
        $this->manager->defineBool('new-ui', false);
        $this->manager->set('new-ui', true, 'user-123');
        self::assertTrue($this->manager->isActive('new-ui', 'user-123'));
        self::assertFalse($this->manager->isActive('new-ui', 'user-456'));
    }

    #[Test]
    public function percentage_rollout_zero_percent(): void
    {
        $this->manager->definePercentage('beta-access', 0);
        // 0% → no one gets it
        for ($i = 1; $i <= 100; $i++) {
            self::assertFalse($this->manager->isActive('beta-access', "user-{$i}"));
        }
    }

    #[Test]
    public function percentage_rollout_hundred_percent(): void
    {
        $this->manager->definePercentage('beta-access', 100);
        // 100% → everyone gets it
        for ($i = 1; $i <= 100; $i++) {
            self::assertTrue($this->manager->isActive('beta-access', "user-{$i}"));
        }
    }

    #[Test]
    public function percentage_rollout_is_deterministic(): void
    {
        $this->manager->definePercentage('beta-access', 25);

        // Same scope should always get the same result
        $result1 = $this->manager->isActive('beta-access', 'user-42');
        $result2 = $this->manager->isActive('beta-access', 'user-42');
        $result3 = $this->manager->isActive('beta-access', 'user-42');

        self::assertSame($result1, $result2);
        self::assertSame($result2, $result3);
    }

    #[Test]
    public function percentage_rollout_approximates_target(): void
    {
        $this->manager->definePercentage('beta-access', 50);

        $active = 0;
        for ($i = 1; $i <= 1000; $i++) {
            if ($this->manager->isActive('beta-access', "user-{$i}")) {
                $active++;
            }
        }

        // 50% of 1000 = ~500, allow ±10% tolerance
        self::assertGreaterThan(400, $active);
        self::assertLessThan(600, $active);
    }

    #[Test]
    public function ab_test_returns_valid_variant(): void
    {
        $this->manager->defineAB('button-color', [
            'red'   => 50,
            'blue'  => 50,
        ]);

        $variant = $this->manager->value('button-color', 'user-42');

        self::assertContains($variant, ['red', 'blue']);
    }

    #[Test]
    public function ab_test_is_deterministic(): void
    {
        $this->manager->defineAB('button-color', [
            'red'   => 50,
            'blue'  => 50,
        ]);

        $v1 = $this->manager->value('button-color', 'user-42');
        $v2 = $this->manager->value('button-color', 'user-42');

        self::assertSame($v1, $v2);
    }

    #[Test]
    public function ab_test_distributes_variants(): void
    {
        $this->manager->defineAB('layout', [
            'a' => 70,
            'b' => 30,
        ]);

        $counts = ['a' => 0, 'b' => 0];
        for ($i = 1; $i <= 1000; $i++) {
            $variant = $this->manager->value('layout', "user-{$i}");
            $counts[$variant]++;
        }

        // 70/30 split with tolerance
        self::assertGreaterThan(600, $counts['a']);
        self::assertLessThan(800, $counts['a']);
        self::assertGreaterThan(200, $counts['b']);
        self::assertLessThan(400, $counts['b']);
    }

    #[Test]
    public function for_scope_sets_default_scope(): void
    {
        $this->manager->defineBool('new-ui', false);
        $this->manager->set('new-ui', true, 'user-123');

        $this->manager->forScope('user-123');
        self::assertTrue($this->manager->isActive('new-ui'));

        // Reset scope
        $this->manager->forScope(null);
        self::assertFalse($this->manager->isActive('new-ui'));
    }

    #[Test]
    public function delete_removes_override(): void
    {
        $this->manager->defineBool('new-ui', false);
        $this->manager->set('new-ui', true);
        self::assertTrue($this->manager->isActive('new-ui'));

        $this->manager->delete('new-ui');
        self::assertFalse($this->manager->isActive('new-ui'));
    }

    #[Test]
    public function undefined_flag_returns_null(): void
    {
        self::assertNull($this->manager->value('nonexistent'));
        self::assertFalse($this->manager->isActive('nonexistent'));
    }

    #[Test]
    public function defined_flags_returns_all_names(): void
    {
        $this->manager->defineBool('flag-a', true);
        $this->manager->defineBool('flag-b', false);
        $this->manager->definePercentage('flag-c', 50);

        $names = $this->manager->definedFlags();
        self::assertCount(3, $names);
        self::assertContains('flag-a', $names);
        self::assertContains('flag-b', $names);
        self::assertContains('flag-c', $names);
    }

    #[Test]
    public function custom_resolver(): void
    {
        $this->manager->define('custom-flag', function(mixed $scope): bool {
            return is_string($scope) && str_starts_with($scope, 'admin-');
        });

        self::assertTrue($this->manager->isActive('custom-flag', 'admin-42'));
        self::assertFalse($this->manager->isActive('custom-flag', 'user-42'));
    }
}
