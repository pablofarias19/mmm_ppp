<?php
// Load .env file - check multiple possible locations
$possibleEnvFiles = [
    dirname(__DIR__, 2) . '/.env',           // /project/.env
    dirname(__DIR__) . '/../.env',              // parent of config/.env
    __DIR__ . '/../.env',                    // config/../.env
];
$envFile = null;
foreach ($possibleEnvFiles as $file) {
    if (file_exists($file)) {
        $envFile = $file;
        break;
    }
}
if ($envFile && file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value);
            $value = trim($value, '"\'');
            if (!isset($_ENV[$name])) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

return [
    "host"     => $_ENV['DB_HOST']    ?? getenv('DB_HOST')    ?: 'localhost',
    "database" => $_ENV['DB_NAME']    ?? getenv('DB_NAME')    ?: '',
    "username" => $_ENV['DB_USER']    ?? getenv('DB_USER')    ?: '',
    "password" => $_ENV['DB_PASS']    ?? getenv('DB_PASS')    ?: '',
    "charset"  => $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4',
];
