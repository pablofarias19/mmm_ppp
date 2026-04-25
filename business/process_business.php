<?php
/**
 * Procesamiento de negocios
 * Funciones para agregar, actualizar y eliminar negocios
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/mapita_notifications.php';

// Si existe el modelo Business, úsalo
if (class_exists('\\App\\Models\\Business')) {
    // Importar namespace
    class_alias('\\App\\Models\\Business', 'Business');
}

function mapitaAllowedBusinessTypes(): array {
    return [
        'restaurante','cafeteria','bar','panaderia','heladeria','pizzeria',
        'supermercado','comercio','autos_venta','motos_venta','indumentaria','ferreteria','electronica','muebleria','floristeria','libreria',
        'productora_audiovisual','escuela_musicos','taller_artes','biodecodificacion','libreria_cristiana',
        'farmacia','hospital','odontologia','veterinaria','optica',
        'salon_belleza','barberia','spa','gimnasio',
        'banco','inmobiliaria','seguros','abogado','contador','taller','construccion','remate','arquitectura','ingenieria',
        'academia','escuela','hotel','turismo','cine',
        'medico_pediatra','medico_traumatologo','laboratorio','ingenieria_civil','astrologo','grafica',
        'alquiler_mobiliario_fiestas','propalacion_musica','animacion_fiestas','zapatero','gas_en_garrafa',
        'videojuegos','seguridad','electricista','gasista','maestro_particular','asistencia_ancianos','enfermeria',
        'obra_de_arte',
        'musico','cantante','bailarin','actor','actriz','director_artistico','guionista','escenografo',
        'fotografo_artistico','productor_artistico','maquillador','pintor','poeta','musicalizador',
        'editor_grafico','asistente_artistico',
        'otros',
    ];
}

function mapitaRestrictedBusinessTypes(): array {
    return ['inmobiliaria', 'seguros', 'abogado'];
}

function mapitaBusinessRequiresAdminApproval(string $businessType): bool {
    return in_array($businessType, mapitaRestrictedBusinessTypes(), true);
}

function isMissingMapitaColumnError(PDOException $e): bool {
    $sqlState = $e->getCode();
    $driverCode = (int)($e->errorInfo[1] ?? 0);
    if ($sqlState === '42S22' || $driverCode === 1054) {
        return true;
    }
    return stripos($e->getMessage(), 'mapita_id') !== false;
}

// ─── Input validation ─────────────────────────────────────────────────────────

/**
 * Valida y saneando los datos de un negocio.
 * @param array $data Datos crudos del formulario.
 * @return array ['valid' => bool, 'errors' => string[], 'data' => array]
 */
function validateBusinessData(array $data): array {
    $errors = [];
    $clean  = [];

    // Nombre (obligatorio, 1-255 chars)
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        $errors[] = 'El nombre del negocio es obligatorio.';
    } elseif (mb_strlen($name) > 255) {
        $errors[] = 'El nombre no puede superar los 255 caracteres.';
    }
    $clean['name'] = $name;

    // Dirección (obligatorio, 1-500 chars)
    $address = trim($data['address'] ?? '');
    if ($address === '') {
        $errors[] = 'La dirección es obligatoria.';
    } elseif (mb_strlen($address) > 500) {
        $errors[] = 'La dirección no puede superar los 500 caracteres.';
    }
    $clean['address'] = $address;

    // Tipo de negocio (obligatorio, lista permitida)
    $allowedTypes = mapitaAllowedBusinessTypes();
    $businessType = trim($data['business_type'] ?? '');
    if (!in_array($businessType, $allowedTypes, true)) {
        $errors[] = 'El tipo de negocio no es válido.';
    }
    $clean['business_type'] = $businessType;

    // Latitud y Longitud (obligatorios, numéricos y en rango)
    $lat = $data['lat'] ?? '';
    $lng = $data['lng'] ?? '';
    if ($lat === '' || $lng === '') {
        $errors[] = 'Las coordenadas son obligatorias. Haz clic en el mapa para seleccionar la ubicación.';
    } else {
        $latF = filter_var($lat, FILTER_VALIDATE_FLOAT);
        $lngF = filter_var($lng, FILTER_VALIDATE_FLOAT);
        if ($latF === false || $latF < -90 || $latF > 90) {
            $errors[] = 'La latitud no es válida (debe estar entre -90 y 90).';
        }
        if ($lngF === false || $lngF < -180 || $lngF > 180) {
            $errors[] = 'La longitud no es válida (debe estar entre -180 y 180).';
        }
        $clean['lat'] = $latF;
        $clean['lng'] = $lngF;
    }

    // Teléfono (opcional, máx 30 chars, solo caracteres permitidos)
    $phone = trim($data['phone'] ?? '');
    if ($phone !== '' && !preg_match('/^[\d\s\+\-\(\)\.]{1,30}$/', $phone)) {
        $errors[] = 'El teléfono contiene caracteres no válidos (máx. 30 dígitos/símbolos).';
    }
    $clean['phone'] = $phone ?: null;

    // Email (opcional, formato válido, máx 255)
    $email = trim($data['email'] ?? '');
    if ($email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors[] = 'El correo electrónico no es válido.';
        }
    }
    $clean['email'] = $email ?: null;

    // Sitio web (opcional, URL válida, máx 500)
    $website = trim($data['website'] ?? '');
    if ($website !== '') {
        if (!filter_var($website, FILTER_VALIDATE_URL) || mb_strlen($website) > 500) {
            $errors[] = 'El sitio web no es una URL válida.';
        }
    }
    $clean['website'] = $website ?: null;

    // Descripción (opcional, máx 2000 chars)
    $description = trim($data['description'] ?? '');
    if (mb_strlen($description) > 2000) {
        $errors[] = 'La descripción no puede superar los 2000 caracteres.';
    }
    $clean['description'] = $description ?: null;

    // Rango de precio — campo interno, no expuesto en el formulario (default 3)
    $priceRange = (int)($data['price_range'] ?? 3);
    if ($priceRange < 1 || $priceRange > 5) $priceRange = 3;
    $clean['price_range'] = $priceRange;

    // Redes sociales
    $instagram = trim($data['instagram'] ?? '');
    if ($instagram !== '' && mb_strlen($instagram) > 100) {
        $errors[] = 'El usuario de Instagram no puede superar 100 caracteres.';
    }
    $clean['instagram'] = $instagram ?: null;

    $facebook = trim($data['facebook'] ?? '');
    if ($facebook !== '' && mb_strlen($facebook) > 100) {
        $errors[] = 'El usuario de Facebook no puede superar 100 caracteres.';
    }
    $clean['facebook'] = $facebook ?: null;

    $tiktok = trim($data['tiktok'] ?? '');
    if ($tiktok !== '' && mb_strlen($tiktok) > 100) {
        $errors[] = 'El usuario de TikTok no puede superar 100 caracteres.';
    }
    $clean['tiktok'] = $tiktok ?: null;

    // Certificaciones
    $certifications = trim($data['certifications'] ?? '');
    if (mb_strlen($certifications) > 500) {
        $errors[] = 'Las certificaciones no pueden superar 500 caracteres.';
    }
    $clean['certifications'] = $certifications ?: null;

    // Checkboxes (booleanos)
    $clean['has_delivery']      = isset($data['has_delivery']) && $data['has_delivery'] ? 1 : 0;
    $clean['has_card_payment']  = isset($data['has_card_payment']) && $data['has_card_payment'] ? 1 : 0;
    $clean['is_franchise']      = isset($data['is_franchise']) && $data['is_franchise'] ? 1 : 0;
    $clean['verified']          = isset($data['verified']) && $data['verified'] ? 1 : 0;

    // Campos de horario, sub-tipo y categorías — válidos para todos los tipos de negocio
    $clean['tipo_comercio']        = mb_substr(trim($data['tipo_comercio']        ?? ''), 0, 255) ?: null;
    $clean['horario_apertura']     = trim($data['horario_apertura']     ?? '') ?: null;
    $clean['horario_cierre']       = trim($data['horario_cierre']       ?? '') ?: null;
    $clean['dias_cierre']          = mb_substr(trim($data['dias_cierre']          ?? ''), 0, 255) ?: null;
    $clean['categorias_productos'] = mb_substr(trim($data['categorias_productos'] ?? ''), 0, 500) ?: null;
    // Timezone IANA del negocio — validar contra lista oficial
    $rawTz = trim($data['timezone'] ?? '');
    $clean['timezone'] = ($rawTz && isValidTimezone($rawTz))
        ? $rawTz
        : 'America/Argentina/Buenos_Aires';

    // Campos i18n / l10n ──────────────────────────────────────────────────────
    $rawCountry = strtoupper(trim($data['country_code'] ?? ''));
    static $flatCountryCodes = null;
    if ($flatCountryCodes === null) {
        $flatCountryCodes = array_merge(...array_values(getCountryOptions()));
    }
    $clean['country_code'] = (strlen($rawCountry) === 2 && isset($flatCountryCodes[$rawCountry]))
        ? $rawCountry
        : null;

    $rawLang = strtolower(trim($data['language_code'] ?? ''));
    $clean['language_code'] = isset(getLanguageOptions()[$rawLang]) ? $rawLang : null;

    $rawCurrency = strtoupper(trim($data['currency_code'] ?? ''));
    $clean['currency_code'] = (preg_match('/^[A-Z]{3}$/', $rawCurrency))
        ? $rawCurrency
        : ($clean['country_code'] ? getCurrencyByCountry($clean['country_code']) : null);

    $rawPhone = trim($data['phone_country_code'] ?? '');
    $clean['phone_country_code'] = (preg_match('/^\+\d{1,5}$/', $rawPhone))
        ? $rawPhone
        : ($clean['country_code'] ? getPhoneCodeByCountry($clean['country_code']) : null);

    $allowedFormats = ['ar', 'us', 'jp', 'eu', 'br', 'mx', 'cn', 'kr', 'ar_rtl'];
    $rawFormat      = trim($data['address_format'] ?? '');
    $clean['address_format'] = in_array($rawFormat, $allowedFormats, true) ? $rawFormat : null;

    return [
        'valid'  => empty($errors),
        'errors' => $errors,
        'data'   => $clean,
    ];
}

/**
 * Agrega un nuevo negocio a la base de datos
 * @param array $data Datos del negocio
 * @param int $userId ID del usuario
 * @return array Resultado de la operación
 */
function addBusiness($data, $userId) {
    try {
        $validation = validateBusinessData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => implode(' ', $validation['errors'])];
        }
        $data = $validation['data'];

        $db = getDbConnection();

        // Comenzar transacción
        $db->beginTransaction();

        $requiresApproval = mapitaBusinessRequiresAdminApproval((string)$data['business_type']) && !isAdmin();
        $initialVisible   = $requiresApproval ? 0 : 1;

        // Insertar negocio básico
        $stmt = $db->prepare("
            INSERT INTO businesses (
                name, address, lat, lng, business_type, phone,
                email, website, description, price_range, user_id, visible,
                instagram, facebook, tiktok, certifications, has_delivery,
                has_card_payment, is_franchise, verified,
                country_code, language_code, currency_code, phone_country_code, address_format,
                created_at
            ) VALUES (
                :name, :address, :lat, :lng, :business_type, :phone,
                :email, :website, :description, :price_range, :user_id, :visible,
                :instagram, :facebook, :tiktok, :certifications, :has_delivery,
                :has_card_payment, :is_franchise, :verified,
                :country_code, :language_code, :currency_code, :phone_country_code, :address_format,
                NOW()
            )
        ");
        $stmt->execute([
            ':name'              => $data['name'],
            ':address'           => $data['address'],
            ':lat'               => $data['lat'] ?? null,
            ':lng'               => $data['lng'] ?? null,
            ':business_type'     => $data['business_type'],
            ':phone'             => $data['phone'],
            ':email'             => $data['email'],
            ':website'           => $data['website'],
            ':description'       => $data['description'],
            ':price_range'       => $data['price_range'],
            ':user_id'           => (int)$userId,
            ':visible'           => $initialVisible,
            ':instagram'         => $data['instagram'],
            ':facebook'          => $data['facebook'],
            ':tiktok'            => $data['tiktok'],
            ':certifications'    => $data['certifications'],
            ':has_delivery'      => $data['has_delivery'],
            ':has_card_payment'  => $data['has_card_payment'],
            ':is_franchise'      => $data['is_franchise'],
            ':verified'          => $data['verified'],
            ':country_code'      => $data['country_code']       ?? null,
            ':language_code'     => $data['language_code']      ?? null,
            ':currency_code'     => $data['currency_code']      ?? null,
            ':phone_country_code'=> $data['phone_country_code'] ?? null,
            ':address_format'    => $data['address_format']     ?? null,
        ]);

        $businessId = $db->lastInsertId();

        if ($requiresApproval && mapitaColumnExists($db, 'businesses', 'status')) {
            $db->prepare("UPDATE businesses SET status = 'pending' WHERE id = ?")->execute([$businessId]);
        }

        // Guardar horarios, sub-tipo y categorías para todos los tipos de negocio
        $hasExtended = $data['tipo_comercio'] || $data['horario_apertura'] || $data['horario_cierre']
                       || $data['dias_cierre'] || $data['categorias_productos'];
        if ($hasExtended) {
            $db->prepare("
                INSERT INTO comercios (
                    business_id, tipo_comercio, horario_apertura, horario_cierre,
                    dias_cierre, timezone, categorias_productos
                ) VALUES (
                    :business_id, :tipo_comercio, :horario_apertura, :horario_cierre,
                    :dias_cierre, :timezone, :categorias_productos
                )
            ")->execute([
                ':business_id'          => $businessId,
                ':tipo_comercio'        => $data['tipo_comercio']        ?? null,
                ':horario_apertura'     => $data['horario_apertura']     ?? null,
                ':horario_cierre'       => $data['horario_cierre']       ?? null,
                ':dias_cierre'          => $data['dias_cierre']          ?? null,
                ':timezone'             => $data['timezone']             ?? 'America/Argentina/Buenos_Aires',
                ':categorias_productos' => $data['categorias_productos'] ?? null,
            ]);
        }

        // Confirmar transacción
        $db->commit();

        $owner = mapitaGetUserContactById($db, (int)$userId);
        mapitaSendUserNotificationEmail(
            $owner['email'] ?? null,
            'MAPITA | Confirmación de operación: alta de negocio',
            'Alta de negocio',
            [
                'Negocio' => (string)$data['name'],
                'Tipo' => (string)$data['business_type'],
                'ID' => (string)$businessId,
                'Estado' => $initialVisible ? 'Publicado' : 'Pendiente de aprobación administrativa',
                'Fecha' => date('d/m/Y H:i'),
            ]
        );

        return [
            'success'     => true,
            'message'     => $requiresApproval
                ? 'Negocio registrado correctamente. Quedó pendiente de aprobación administrativa para su publicación.'
                : 'Negocio agregado correctamente',
            'business_id' => $businessId,
        ];

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error en addBusiness: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al agregar negocio.'];
    }
}

/**
 * Actualiza un negocio existente.
 * @param int   $businessId ID del negocio a actualizar
 * @param array $data       Nuevos datos del formulario
 * @param int   $userId     ID del usuario (para verificar propiedad)
 * @return array Resultado de la operación
 */
function updateBusiness($businessId, $data, $userId) {
    try {
        $businessId = (int)$businessId;
        $userId     = (int)$userId;

        $validation = validateBusinessData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => implode(' ', $validation['errors'])];
        }
        $data = $validation['data'];

        $db = getDbConnection();

        // Verificar permisos (owner, delegado admin o superadmin)
        $stmt = $db->prepare("SELECT user_id, business_type FROM businesses WHERE id = ?");
        $stmt->execute([$businessId]);
        $business = $stmt->fetch();

        if (!$business) {
            return ['success' => false, 'message' => 'Negocio no encontrado.'];
        }
        if (!canManageBusiness($userId, $businessId)) {
            return ['success' => false, 'message' => 'No tienes permiso para editar este negocio.'];
        }

        $requiresApprovalNow = mapitaBusinessRequiresAdminApproval((string)$data['business_type']);
        $wasRestricted       = mapitaBusinessRequiresAdminApproval((string)($business['business_type'] ?? ''));
        $forcePendingByTypeChange = $requiresApprovalNow && !$wasRestricted && !isAdmin();

        $db->beginTransaction();

        // Actualizar negocio básico
        $stmt = $db->prepare("
            UPDATE businesses
            SET name = :name, address = :address, lat = :lat, lng = :lng,
                business_type = :business_type, phone = :phone, email = :email,
                website = :website, description = :description,
                price_range = :price_range, instagram = :instagram,
                facebook = :facebook, tiktok = :tiktok,
                certifications = :certifications, has_delivery = :has_delivery,
                has_card_payment = :has_card_payment, is_franchise = :is_franchise,
                verified = :verified, visible = :visible,
                country_code = :country_code, language_code = :language_code,
                currency_code = :currency_code, phone_country_code = :phone_country_code,
                address_format = :address_format,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'              => $data['name'],
            ':address'           => $data['address'],
            ':lat'               => $data['lat'] ?? null,
            ':lng'               => $data['lng'] ?? null,
            ':business_type'     => $data['business_type'],
            ':phone'             => $data['phone'],
            ':email'             => $data['email'],
            ':website'           => $data['website'],
            ':description'       => $data['description'],
            ':price_range'       => $data['price_range'],
            ':instagram'         => $data['instagram'],
            ':facebook'          => $data['facebook'],
            ':tiktok'            => $data['tiktok'],
            ':certifications'    => $data['certifications'],
            ':has_delivery'      => $data['has_delivery'],
            ':has_card_payment'  => $data['has_card_payment'],
            ':is_franchise'      => $data['is_franchise'],
            ':verified'          => $data['verified'],
            ':visible'           => $forcePendingByTypeChange ? 0 : (int)($business['visible'] ?? 1),
            ':country_code'      => $data['country_code']       ?? null,
            ':language_code'     => $data['language_code']      ?? null,
            ':currency_code'     => $data['currency_code']      ?? null,
            ':phone_country_code'=> $data['phone_country_code'] ?? null,
            ':address_format'    => $data['address_format']     ?? null,
            ':id'                => $businessId,
        ]);

        if ($forcePendingByTypeChange && mapitaColumnExists($db, 'businesses', 'status')) {
            $db->prepare("UPDATE businesses SET status = 'pending' WHERE id = ?")->execute([$businessId]);
        }

        // Upsert en comercios — se guarda para todos los tipos de negocio
        $check = $db->prepare("SELECT id FROM comercios WHERE business_id = ?");
        $check->execute([$businessId]);
        if ($check->fetch()) {
            $stmtC = $db->prepare("
                UPDATE comercios
                SET tipo_comercio = :tipo_comercio, horario_apertura = :horario_apertura,
                    horario_cierre = :horario_cierre, dias_cierre = :dias_cierre,
                    timezone = :timezone, categorias_productos = :categorias_productos
                WHERE business_id = :business_id
            ");
        } else {
            $stmtC = $db->prepare("
                INSERT INTO comercios (
                    business_id, tipo_comercio, horario_apertura, horario_cierre,
                    dias_cierre, timezone, categorias_productos
                ) VALUES (
                    :business_id, :tipo_comercio, :horario_apertura, :horario_cierre,
                    :dias_cierre, :timezone, :categorias_productos
                )
            ");
        }
        $stmtC->execute([
            ':business_id'          => $businessId,
            ':tipo_comercio'        => $data['tipo_comercio']        ?? null,
            ':horario_apertura'     => $data['horario_apertura']     ?? null,
            ':horario_cierre'       => $data['horario_cierre']       ?? null,
            ':dias_cierre'          => $data['dias_cierre']          ?? null,
            ':timezone'             => $data['timezone']             ?? 'America/Argentina/Buenos_Aires',
            ':categorias_productos' => $data['categorias_productos'] ?? null,
        ]);

        $db->commit();

        // Optional: update encuestas_override if admin submits the field
        if (isAdmin() && array_key_exists('encuestas_override', $data)) {
            $validOverrides = ['heredar', 'habilitada', 'deshabilitada'];
            $ov = in_array($data['encuestas_override'], $validOverrides, true) ? $data['encuestas_override'] : 'heredar';
            try {
                $db->prepare("UPDATE businesses SET encuestas_override = ? WHERE id = ?")
                   ->execute([$ov, $businessId]);
            } catch (PDOException $e) {
                // Column not yet migrated — silently ignore
                if ($e->getCode() !== '42S22' && (int)($e->errorInfo[1] ?? 0) !== 1054) throw $e;
            }
        }

        $owner = mapitaGetUserContactById($db, (int)$business['user_id']);
        mapitaSendUserNotificationEmail(
            $owner['email'] ?? null,
            'MAPITA | Confirmación de operación: edición de negocio',
            'Edición de negocio',
            [
                'Negocio' => (string)$data['name'],
                'Tipo' => (string)$data['business_type'],
                'ID' => (string)$businessId,
                'Estado' => $forcePendingByTypeChange ? 'Pendiente de aprobación administrativa' : 'Actualizado',
                'Fecha' => date('d/m/Y H:i'),
            ]
        );

        return [
            'success' => true,
            'message' => $forcePendingByTypeChange
                ? 'Negocio actualizado. Por cambio de rubro, quedó pendiente de aprobación administrativa para su publicación.'
                : 'Negocio actualizado correctamente.'
        ];

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error en updateBusiness: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al actualizar negocio.'];
    }
}

/**
 * Elimina un negocio
 * @param int $businessId ID del negocio
 * @param int $userId ID del usuario (para verificar propiedad)
 * @return array Resultado de la operación
 */
function deleteBusiness($businessId, $userId) {
    try {
        $businessId = (int)$businessId;
        $userId     = (int)$userId;

        $db = getDbConnection();

        // Verificar permisos (owner, delegado admin o superadmin)
        $stmt = $db->prepare("SELECT user_id, name FROM businesses WHERE id = ?");
        $stmt->execute([$businessId]);
        $business = $stmt->fetch();

        if (!$business) {
            return ['success' => false, 'message' => 'Negocio no encontrado'];
        }

        if (!canManageBusiness($userId, $businessId)) {
            return ['success' => false, 'message' => 'No tienes permiso para eliminar este negocio'];
        }

        $db->beginTransaction();

        // Eliminar datos de comercio si existen
        $db->prepare("DELETE FROM comercios WHERE business_id = ?")->execute([$businessId]);

        // Eliminar negocio
        $db->prepare("DELETE FROM businesses WHERE id = ?")->execute([$businessId]);

        $db->commit();

        $owner = mapitaGetUserContactById($db, (int)$business['user_id']);
        mapitaSendUserNotificationEmail(
            $owner['email'] ?? null,
            'MAPITA | Confirmación de operación: eliminación de negocio',
            'Eliminación de negocio',
            [
                'Negocio' => (string)($business['name'] ?? ('ID ' . $businessId)),
                'ID' => (string)$businessId,
                'Fecha' => date('d/m/Y H:i'),
            ]
        );

        return ['success' => true, 'message' => 'Negocio eliminado correctamente'];

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error en deleteBusiness: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al eliminar negocio.'];
    }
}

/**
 * Cambia la visibilidad de un negocio.
 * @param int $businessId ID del negocio
 * @param int $userId     ID del usuario propietario
 * @return array Resultado de la operación
 */
function toggleBusinessVisibility($businessId, $userId) {
    try {
        $businessId = (int)$businessId;
        $userId     = (int)$userId;

        $db = getDbConnection();

        $stmt = $db->prepare("SELECT user_id, visible, business_type FROM businesses WHERE id = ?");
        $stmt->execute([$businessId]);
        $business = $stmt->fetch();

        if (!$business) {
            return ['success' => false, 'message' => 'Negocio no encontrado.'];
        }
        if (!canManageBusiness($userId, $businessId)) {
            return ['success' => false, 'message' => 'No tienes permiso para modificar este negocio.'];
        }

        $newVisible = $business['visible'] ? 0 : 1;
        if ($newVisible === 1
            && mapitaBusinessRequiresAdminApproval((string)($business['business_type'] ?? ''))
            && !isAdmin()) {
            return ['success' => false, 'message' => 'Este rubro requiere aprobación expresa del administrador para publicarse.'];
        }
        $db->prepare("UPDATE businesses SET visible = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$newVisible, $businessId]);

        return [
            'success' => true,
            'message' => $newVisible ? 'Negocio publicado.' : 'Negocio ocultado.',
            'visible' => $newVisible,
        ];

    } catch (Exception $e) {
        error_log("Error en toggleBusinessVisibility: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al cambiar visibilidad.'];
    }
}

/**
 * Obtiene datos específicos de un comercio
 * @param int $businessId ID del negocio
 * @return array|null Datos del comercio o null si no existe
 */
function getComercioData($businessId) {
    try {
        $db   = getDbConnection();
        $stmt = $db->prepare("SELECT * FROM comercios WHERE business_id = ?");
        $stmt->execute([(int)$businessId]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        error_log("Error al obtener datos de comercio: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifica si un negocio puede crear encuestas.
 * Regla: si tiene override 'habilitada' o 'deshabilitada', usa ese valor.
 * Si es 'heredar' (default), hereda del permiso de la industria relacionada.
 *
 * @param int $businessId
 * @return bool
 */
function canCreateSurvey(int $businessId): bool {
    try {
        $db = getDbConnection();

        // Obtener override del negocio
        $stmt = $db->prepare("SELECT encuestas_override FROM businesses WHERE id = ? LIMIT 1");
        $stmt->execute([$businessId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $override = $row['encuestas_override'] ?? 'heredar';
        if ($override === 'habilitada')    return true;
        if ($override === 'deshabilitada') return false;

        // Heredar de industria
        $stmt2 = $db->prepare(
            "SELECT i.encuestas_permitidas
             FROM industries i
             WHERE i.business_id = ?
             LIMIT 1"
        );
        $stmt2->execute([$businessId]);
        $ind = $stmt2->fetch(PDO::FETCH_ASSOC);
        return !empty($ind['encuestas_permitidas']);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Duplica un negocio: clona todos sus datos excepto la ubicación geoespacial.
 * Retorna el ID del nuevo negocio.
 *
 * @param int $businessId ID del negocio original
 * @param int $userId     ID del usuario que realiza la acción
 * @return array ['success' => bool, 'business_id' => int|null, 'message' => string]
 */
function duplicateBusiness(int $businessId, int $userId): array {
    try {
        $db = getDbConnection();

        if (!canManageBusiness($userId, $businessId)) {
            return ['success' => false, 'business_id' => null, 'message' => 'Sin permisos para duplicar este negocio.'];
        }

        // Cargar negocio original
        $stmt = $db->prepare("SELECT * FROM businesses WHERE id = ? LIMIT 1");
        $stmt->execute([$businessId]);
        $orig = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orig) {
            return ['success' => false, 'business_id' => null, 'message' => 'Negocio no encontrado.'];
        }

        $db->beginTransaction();

        // Columnas a excluir (geolocalización + id)
        $excludeCols = ['id', 'lat', 'lng', 'created_at', 'updated_at'];

        $cols   = [];
        $vals   = [];
        $params = [];
        foreach ($orig as $col => $val) {
            if (in_array($col, $excludeCols, true)) continue;
            $cols[]   = $col;
            $vals[]   = '?';
            $params[] = $val;
        }
        // Ajustar campos para el duplicado
        $nameIdx = array_search('name', $cols);
        if ($nameIdx !== false) {
            $params[$nameIdx] = ($orig['name'] ?? 'Negocio') . ' (copia)';
        }
        // lat/lng: NULL para forzar al usuario a setear ubicación
        $cols[]   = 'lat';   $vals[]   = '?'; $params[] = null;
        $cols[]   = 'lng';   $vals[]   = '?'; $params[] = null;
        $cols[]   = 'created_at'; $vals[] = 'NOW()';
        // No pasar created_at como param — se usa NOW() literal
        array_pop($params); // Sacar el null que se añadió para created_at

        $sql = 'INSERT INTO businesses (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')';
        // Reemplazar el '?' de created_at (último) por NOW()
        $sql = preg_replace('/\?$/', 'NOW()', $sql);

        $stmt2 = $db->prepare($sql);
        $stmt2->execute($params);
        $newId = (int)$db->lastInsertId();

        // Duplicar datos de comercio (horarios, etc.) si existen
        $comercio = $db->prepare("SELECT * FROM comercios WHERE business_id = ? LIMIT 1");
        $comercio->execute([$businessId]);
        $com = $comercio->fetch(PDO::FETCH_ASSOC);
        if ($com) {
            unset($com['id']);
            $com['business_id'] = $newId;
            $comCols = array_keys($com);
            $comVals = array_fill(0, count($comCols), '?');
            $db->prepare(
                'INSERT INTO comercios (' . implode(',', $comCols) . ') VALUES (' . implode(',', $comVals) . ')'
            )->execute(array_values($com));
        }

        $db->commit();

        return ['success' => true, 'business_id' => $newId, 'message' => 'Negocio duplicado correctamente. Por favor, asigná la nueva ubicación en el mapa.'];

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        error_log("Error en duplicateBusiness: " . $e->getMessage());
        return ['success' => false, 'business_id' => null, 'message' => 'Error al duplicar el negocio.'];
    }
}

// ─── Image upload ─────────────────────────────────────────────────────────────

/**
 * Procesa la subida de hasta 3 imágenes para un negocio.
 *
 * @param int   $businessId ID del negocio.
 * @param array $files      Array de archivos (e.g. $_FILES['images']).
 * @param int   $userId     ID del usuario (para verificar propiedad).
 * @return array ['success' => bool, 'message' => string, 'paths' => string[]]
 */
function uploadBusinessImages(int $businessId, array $files, int $userId): array {
    $db   = getDbConnection();
    $stmt = $db->prepare("SELECT user_id FROM businesses WHERE id = ?");
    $stmt->execute([$businessId]);
    $biz  = $stmt->fetch();

    if (!$biz) {
        return ['success' => false, 'message' => 'Negocio no encontrado.'];
    }
    if ((int)$biz['user_id'] !== $userId) {
        return ['success' => false, 'message' => 'No tienes permiso para subir imágenes a este negocio.'];
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize     = 5 * 1024 * 1024; // 5 MB por imagen
    $maxImages   = 3;
    $uploadDir   = dirname(__DIR__) . '/uploads/businesses/' . $businessId . '/';
    $saved       = [];

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Normalizar la estructura de $_FILES para múltiples archivos
    $normalized = [];
    if (isset($files['name']) && is_array($files['name'])) {
        for ($i = 0; $i < min(count($files['name']), $maxImages); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $normalized[] = [
                    'name'     => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size'     => $files['size'][$i],
                    'error'    => $files['error'][$i],
                ];
            }
        }
    } elseif (isset($files['tmp_name']) && $files['error'] === UPLOAD_ERR_OK) {
        $normalized[] = $files;
    }

    foreach ($normalized as $file) {
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Una de las imágenes supera el tamaño máximo de 5 MB.'];
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMime, true)) {
            return ['success' => false, 'message' => 'Tipo de imagen no permitido. Use JPEG, PNG o WebP.'];
        }

        $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $ext      = $extMap[$mimeType] ?? 'jpg';
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'message' => 'Error al guardar la imagen.'];
        }

        $saved[] = 'uploads/businesses/' . $businessId . '/' . $filename;
    }

    return [
        'success' => true,
        'message' => count($saved) . ' imagen(es) subida(s) correctamente.',
        'paths'   => $saved,
    ];
}
