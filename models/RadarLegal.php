<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

/**
 * Modelo agregador del Radar Legal.
 * Gestiona transport_modes, ports, destinations, restrictions, disputes, contract_types.
 */
class RadarLegal {

    // ── Transport Modes ────────────────────────────────────────────────────────

    public static function getTransportModes(): array {
        try {
            $db = Database::getInstance()->getConnection();
            return $db->query('SELECT * FROM radar_transport_modes ORDER BY mode, name')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('RadarLegal::getTransportModes - ' . $e->getMessage()); return [];
        }
    }

    // ── Ports ──────────────────────────────────────────────────────────────────

    public static function getPorts(?int $transportModeId = null): array {
        try {
            $db = Database::getInstance()->getConnection();
            if ($transportModeId) {
                $stmt = $db->prepare('SELECT * FROM radar_ports WHERE transport_mode_id = ? ORDER BY name');
                $stmt->execute([$transportModeId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $db->query('SELECT * FROM radar_ports ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('RadarLegal::getPorts - ' . $e->getMessage()); return [];
        }
    }

    // ── Destinations ──────────────────────────────────────────────────────────

    public static function getDestinations(?string $direction = null): array {
        try {
            $db = Database::getInstance()->getConnection();
            if ($direction) {
                $stmt = $db->prepare('SELECT * FROM radar_destinations WHERE direction = ? ORDER BY name');
                $stmt->execute([$direction]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $db->query('SELECT * FROM radar_destinations ORDER BY direction, name')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('RadarLegal::getDestinations - ' . $e->getMessage()); return [];
        }
    }

    // ── Restrictions ──────────────────────────────────────────────────────────

    public static function getRestrictions(?string $type = null): array {
        try {
            $db = Database::getInstance()->getConnection();
            if ($type) {
                $stmt = $db->prepare('SELECT * FROM radar_restrictions WHERE restriction_type = ? ORDER BY name');
                $stmt->execute([$type]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $db->query('SELECT * FROM radar_restrictions ORDER BY restriction_type, name')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('RadarLegal::getRestrictions - ' . $e->getMessage()); return [];
        }
    }

    // ── Disputes ──────────────────────────────────────────────────────────────

    public static function getDisputes(?string $type = null): array {
        try {
            $db = Database::getInstance()->getConnection();
            if ($type) {
                $stmt = $db->prepare('SELECT * FROM radar_disputes WHERE dispute_type = ? ORDER BY name');
                $stmt->execute([$type]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $db->query('SELECT * FROM radar_disputes ORDER BY dispute_type, name')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('RadarLegal::getDisputes - ' . $e->getMessage()); return [];
        }
    }

    // ── Contract Types ────────────────────────────────────────────────────────

    public static function getContractTypes(?string $category = null): array {
        try {
            $db = Database::getInstance()->getConnection();
            if ($category) {
                $stmt = $db->prepare('SELECT * FROM radar_contract_types WHERE category = ? ORDER BY name');
                $stmt->execute([$category]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $db->query('SELECT * FROM radar_contract_types ORDER BY category, name')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('RadarLegal::getContractTypes - ' . $e->getMessage()); return [];
        }
    }

    // ── Sector Radar Settings ─────────────────────────────────────────────────

    public static function getSettings(string $sectorType, int $sectorId): ?array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM sector_radar_settings WHERE sector_type = ? AND sector_id = ?');
            $stmt->execute([$sectorType, $sectorId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('RadarLegal::getSettings - ' . $e->getMessage()); return null;
        }
    }

    public static function isEnabled(string $sectorType, int $sectorId): bool {
        $settings = self::getSettings($sectorType, $sectorId);
        if ($settings) return (bool)$settings['enabled'];
        // Fallback: check radar_enabled flag on sector table
        if ($sectorType !== 'industrial' && $sectorType !== 'commercial') {
            return false;
        }
        try {
            $db = Database::getInstance()->getConnection();
            if ($sectorType === 'industrial') {
                $stmt = $db->prepare('SELECT radar_enabled FROM industrial_sectors WHERE id = ?');
            } else {
                $stmt = $db->prepare('SELECT radar_enabled FROM commercial_sectors WHERE id = ?');
            }
            $stmt->execute([$sectorId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (bool)$row['radar_enabled'] : false;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function setEnabled(string $sectorType, int $sectorId, bool $enabled, ?string $notes = null): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT INTO sector_radar_settings (sector_type, sector_id, enabled, notes)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), notes = VALUES(notes), updated_at = NOW()'
            );
            return $stmt->execute([$sectorType, $sectorId, $enabled ? 1 : 0, $notes]);
        } catch (Exception $e) {
            error_log('RadarLegal::setEnabled - ' . $e->getMessage()); return false;
        }
    }
}
