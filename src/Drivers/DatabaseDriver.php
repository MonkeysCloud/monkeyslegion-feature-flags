<?php
declare(strict_types=1);

namespace MonkeysLegion\FeatureFlags\Drivers;

use PDO;
use PDOException;

/**
 * MonKeysLegion Framework — Feature Flags Package
 *
 * Database driver storing flags in a `feature_flags` table.
 *
 * Schema:
 *   CREATE TABLE feature_flags (
 *     id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     name      VARCHAR(255) NOT NULL,
 *     scope     VARCHAR(255) NOT NULL DEFAULT '__global__',
 *     value     TEXT,
 *     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *     updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *     UNIQUE KEY uniq_flag_scope (name, scope)
 *   );
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class DatabaseDriver implements FeatureDriverInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table = 'feature_flags',
    ) {}

    public function get(string $name, mixed $scope = null): mixed
    {
        $scopeKey = $this->scopeKey($scope);

        try {
            $stmt = $this->pdo->prepare(
                "SELECT value FROM {$this->table} WHERE name = ? AND scope = ? LIMIT 1"
            );
            $stmt->execute([$name, $scopeKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row === false) return null;

            return $this->unserialize($row['value']);
        } catch (PDOException) {
            return null;
        }
    }

    public function set(string $name, mixed $value, mixed $scope = null): void
    {
        $scopeKey = $this->scopeKey($scope);
        $serialized = $this->serialize($value);

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (name, scope, value) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)"
        );
        $stmt->execute([$name, $scopeKey, $serialized]);
    }

    public function delete(string $name, mixed $scope = null): void
    {
        $scopeKey = $this->scopeKey($scope);
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE name = ? AND scope = ?"
        );
        $stmt->execute([$name, $scopeKey]);
    }

    public function all(mixed $scope = null): array
    {
        $scopeKey = $this->scopeKey($scope);

        try {
            $stmt = $this->pdo->prepare(
                "SELECT name, value FROM {$this->table} WHERE scope = ?"
            );
            $stmt->execute([$scopeKey]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $flags = [];
            foreach ($rows as $row) {
                $flags[$row['name']] = $this->unserialize($row['value']);
            }
            return $flags;
        } catch (PDOException) {
            return [];
        }
    }

    private function scopeKey(mixed $scope): string
    {
        if ($scope === null) return '__global__';
        if (is_object($scope)) return spl_object_hash($scope);
        if (is_scalar($scope)) return (string) $scope;
        return md5(serialize($scope));
    }

    private function serialize(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function unserialize(string $value): mixed
    {
        $decoded = json_decode($value, true);
        return $decoded === null ? $value : $decoded;
    }
}
