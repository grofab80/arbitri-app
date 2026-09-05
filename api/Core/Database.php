<?php
namespace Api\Core;

use PDO;
use PDOException;

class Database {

    private static ?PDO $pdo = null;

    public static function get(): PDO {
        if (self::$pdo === null) {

            $cfg = require __DIR__ . '/../../config/db.php';

            try {
                self::$pdo = new PDO(
                    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}",
                    $cfg['user'],
                    $cfg['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Errore connessione DB']);
                exit;
            }
        }

        return self::$pdo;
    }
}
