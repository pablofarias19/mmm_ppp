<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../core/helpers.php';

if (!defined('MAX_DELEGATED_ADMINS_PER_ENTITY')) {
    define('MAX_DELEGATED_ADMINS_PER_ENTITY', 3);
}

if (!function_exists('delegationRequireAuthUserId')) {
    function delegationRequireAuthUserId(): int {
        if (empty($_SESSION['user_id'])) {
            respond_error('Se requiere autenticación.', 401);
        }
        return (int)$_SESSION['user_id'];
    }
}

if (!function_exists('delegationReadInput')) {
    function delegationReadInput(): array {
        $json = json_decode(file_get_contents('php://input'), true);
        if (!is_array($json)) $json = [];
        return array_merge($json, $_POST);
    }
}

if (!function_exists('delegationUserInfo')) {
    function delegationUserInfo(PDO $db, int $userId): ?array {
        $stmt = $db->prepare('SELECT id, username, email, is_admin FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('delegationIsSuperadminUser')) {
    function delegationIsSuperadminUser(array $userRow): bool {
        return !empty($userRow['is_admin']);
    }
}

if (!function_exists('delegationGetBusinessOwnerId')) {
    function delegationGetBusinessOwnerId(PDO $db, int $businessId): ?int {
        $stmt = $db->prepare('SELECT user_id FROM businesses WHERE id = ? LIMIT 1');
        $stmt->execute([$businessId]);
        $ownerId = $stmt->fetchColumn();
        return $ownerId !== false ? (int)$ownerId : null;
    }
}

if (!function_exists('delegationGetBrandOwnerId')) {
    function delegationGetBrandOwnerId(PDO $db, int $brandId): ?int {
        if (mapitaTableExists($db, 'brands')) {
            $stmt = $db->prepare('SELECT user_id FROM brands WHERE id = ? LIMIT 1');
            $stmt->execute([$brandId]);
            $ownerId = $stmt->fetchColumn();
            if ($ownerId !== false) return (int)$ownerId;
        }
        if (mapitaTableExists($db, 'marcas')) {
            $stmt = $db->prepare('SELECT usuario_id FROM marcas WHERE id = ? LIMIT 1');
            $stmt->execute([$brandId]);
            $ownerId = $stmt->fetchColumn();
            if ($ownerId !== false) return (int)$ownerId;
        }
        return null;
    }
}

if (!function_exists('delegationCountBusinessAdmins')) {
    function delegationCountBusinessAdmins(PDO $db, int $businessId): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM business_delegations WHERE business_id = ? AND role = 'admin'");
        $stmt->execute([$businessId]);
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('delegationCountBrandAdmins')) {
    function delegationCountBrandAdmins(PDO $db, int $brandId): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM brand_delegations WHERE brand_id = ? AND role = 'admin'");
        $stmt->execute([$brandId]);
        return (int)$stmt->fetchColumn();
    }
}
