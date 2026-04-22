<?php

if (!function_exists('mapitaSanitizeMailHeader')) {
    function mapitaSanitizeMailHeader(string $value): string {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}

if (!function_exists('mapitaBuildNotificationBody')) {
    function mapitaBuildNotificationBody(string $operation, array $details = []): string {
        $lines = [];
        foreach ($details as $label => $value) {
            $label = trim((string)$label);
            $value = trim((string)$value);
            if ($label === '' || $value === '') continue;
            $lines[] = "- {$label}: {$value}";
        }

        $body  = "Estimado/a,\n\n";
        $body .= "Le confirmamos que se registró la siguiente operación en MAPITA:\n";
        $body .= strtoupper($operation) . "\n\n";
        if (!empty($lines)) {
            $body .= "Detalle de la operación:\n" . implode("\n", $lines) . "\n\n";
        }
        $body .= "Nuestro equipo gestiona su solicitud y se encuentra a su disposición para acompañarlo/a.\n\n";
        $body .= "Este mensaje se envía como constancia desde mapita.com.ar.\n\n";
        $body .= "Saludos cordiales,\n";
        $body .= "Equipo MAPITA\n";
        $body .= "https://mapita.com.ar\n";
        return $body;
    }
}

if (!function_exists('mapitaSendUserNotificationEmail')) {
    function mapitaSendUserNotificationEmail(?string $toEmail, string $subject, string $operation, array $details = []): void {
        $toEmail = trim((string)$toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $cleanSubject = mapitaSanitizeMailHeader($subject);
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: MAPITA <no-reply@mapita.com.ar>',
            'Reply-To: soporte@mapita.com.ar',
            'X-Mailer: PHP/' . phpversion(),
        ]);

        $body = mapitaBuildNotificationBody($operation, $details);
        @mail($toEmail, $cleanSubject, $body, $headers);
    }
}

if (!function_exists('mapitaGetUserContactById')) {
    function mapitaGetUserContactById(PDO $db, int $userId): ?array {
        if ($userId <= 0) return null;
        $stmt = $db->prepare('SELECT id, username, email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

