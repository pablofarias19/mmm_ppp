<?php
namespace Core;

class Database {
    private static $instance = null;
    private $connection = null;

    private function __construct() {
        $config_file = dirname(__DIR__) . '/config/database.php';
        if (!file_exists($config_file)) {
            throw new \Exception("Archivo de configuración no encontrado: $config_file");
        }

        $db_config = require $config_file;

        try {
            $this->connection = new \PDO(
                "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
                $db_config['username'],
                $db_config['password'],
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                    \PDO::ATTR_PERSISTENT          => true,
                ]
            );
        } catch (\PDOException $e) {
            throw new \Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
