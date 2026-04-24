<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

/**
 * Modelo Industry — industria creada por un usuario.
 * La tabla `industries` relaciona al usuario con un sector del catálogo admin.
 */
class Industry {
    protected $table = 'industries';

    const VALID_STATUSES       = ['borrador', 'activa', 'archivada'];
    const VALID_EMPLOYEE_RANGES = ['1-10', '11-50', '51-200', '201-500', '500+'];
    const VALID_REVENUE_SCALES  = ['micro', 'pequeña', 'mediana', 'grande', 'corporación'];

    /**
     * Lista industrias con filtros opcionales.
     * Si $userId > 0 y no es admin, filtra por propietario.
     */
    public static function getAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        try {
            $db     = Database::getInstance()->getConnection();
            $where  = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[]  = 'i.user_id = ?';
                $params[] = (int)$filters['user_id'];
            }
            if (!empty($filters['industrial_sector_id'])) {
                $where[]  = 'i.industrial_sector_id = ?';
                $params[] = (int)$filters['industrial_sector_id'];
            }
            if (!empty($filters['status'])) {
                $where[]  = 'i.status = ?';
                $params[] = $filters['status'];
            }
            if (!empty($filters['search'])) {
                $where[]  = 'i.name LIKE ?';
                $params[] = '%' . $filters['search'] . '%';
            }

            $sql = 'SELECT i.*, s.name AS sector_name, s.type AS sector_type
                    FROM industries i
                    LEFT JOIN industrial_sectors s ON s.id = i.industrial_sector_id'
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . ' ORDER BY i.created_at DESC LIMIT ? OFFSET ?';
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error en Industry::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de industrias (para paginación).
     */
    public static function count(array $filters = []): int {
        try {
            $db     = Database::getInstance()->getConnection();
            $where  = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[]  = 'user_id = ?';
                $params[] = (int)$filters['user_id'];
            }
            if (!empty($filters['industrial_sector_id'])) {
                $where[]  = 'industrial_sector_id = ?';
                $params[] = (int)$filters['industrial_sector_id'];
            }
            if (!empty($filters['status'])) {
                $where[]  = 'status = ?';
                $params[] = $filters['status'];
            }
            if (!empty($filters['search'])) {
                $where[]  = 'name LIKE ?';
                $params[] = '%' . $filters['search'] . '%';
            }

            $sql  = 'SELECT COUNT(*) FROM industries' . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('Error en Industry::count - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene una industria por ID (con sector_name).
     */
    public static function getById(int $id): ?array {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'SELECT i.*, s.name AS sector_name, s.type AS sector_type
                 FROM industries i
                 LEFT JOIN industrial_sectors s ON s.id = i.industrial_sector_id
                 WHERE i.id = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('Error en Industry::getById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea una nueva industria. Devuelve el ID o false.
     */
    public static function create(array $data) {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT INTO industries
                    (user_id, industrial_sector_id, business_id, brand_id, name, description,
                     website, contact_email, contact_phone, country, country_code, region, city,
                     employees_range, annual_revenue, certifications,
                     naics_code, isic_code, nace_code, ciiu_code,
                     language_code, currency_code, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                (int)$data['user_id'],
                !empty($data['industrial_sector_id']) ? (int)$data['industrial_sector_id'] : null,
                !empty($data['business_id'])          ? (int)$data['business_id']          : null,
                !empty($data['brand_id'])             ? (int)$data['brand_id']             : null,
                $data['name'],
                $data['description']      ?? null,
                $data['website']          ?? null,
                $data['contact_email']    ?? null,
                $data['contact_phone']    ?? null,
                $data['country']          ?? null,
                $data['country_code']     ?? null,
                $data['region']           ?? null,
                $data['city']             ?? null,
                $data['employees_range']  ?? null,
                $data['annual_revenue']   ?? null,
                $data['certifications']   ?? null,
                $data['naics_code']       ?? null,
                $data['isic_code']        ?? null,
                $data['nace_code']        ?? null,
                $data['ciiu_code']        ?? null,
                $data['language_code']    ?? null,
                $data['currency_code']    ?? null,
                $data['status']           ?? 'borrador',
            ]);
            return $result ? (int)$db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log('Error en Industry::create - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza una industria existente. Devuelve true/false.
     */
    public static function update(int $id, array $data): bool {
        try {
            $db      = Database::getInstance()->getConnection();
            $fields  = [];
            $params  = [];
            $allowed = [
                'industrial_sector_id', 'business_id', 'brand_id', 'name', 'description',
                'website', 'contact_email', 'contact_phone', 'country', 'country_code', 'region', 'city',
                'employees_range', 'annual_revenue', 'certifications',
                'naics_code', 'isic_code', 'nace_code', 'ciiu_code',
                'language_code', 'currency_code', 'status',
            ];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            if (empty($fields)) return false;

            $params[] = $id;
            $stmt = $db->prepare('UPDATE industries SET ' . implode(', ', $fields) . ' WHERE id = ?');
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log('Error en Industry::update - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una industria (solo el propietario o admin).
     */
    public static function delete(int $id): bool {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare('DELETE FROM industries WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('Error en Industry::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Archiva una industria (cambia status a 'archivada').
     */
    public static function archive(int $id): bool {
        return self::update($id, ['status' => 'archivada']);
    }

    /**
     * Valida los datos de entrada. Devuelve array de errores (vacío si OK).
     */
    public static function validate(array $data, bool $requireAll = true): array {
        $errors = [];

        if ($requireAll || isset($data['name'])) {
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') {
                $errors[] = 'El campo "Nombre" es obligatorio.';
            } elseif (mb_strlen($name) > 255) {
                $errors[] = 'El campo "Nombre" no puede superar 255 caracteres.';
            }
        }

        if ($requireAll || isset($data['user_id'])) {
            if (empty($data['user_id']) || (int)$data['user_id'] <= 0) {
                $errors[] = 'El campo "user_id" es obligatorio.';
            }
        }

        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES, true)) {
            $errors[] = 'Estado inválido. Valores permitidos: ' . implode(', ', self::VALID_STATUSES) . '.';
        }

        if (isset($data['employees_range']) && $data['employees_range'] !== null && $data['employees_range'] !== ''
            && !in_array($data['employees_range'], self::VALID_EMPLOYEE_RANGES, true)) {
            $errors[] = 'Rango de empleados inválido.';
        }

        if (isset($data['annual_revenue']) && $data['annual_revenue'] !== null && $data['annual_revenue'] !== ''
            && !in_array($data['annual_revenue'], self::VALID_REVENUE_SCALES, true)) {
            $errors[] = 'Escala de ingresos inválida.';
        }

        if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email de contacto no es válido.';
        }

        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            $errors[] = 'La URL del sitio web no es válida.';
        }

        return $errors;
    }
}
