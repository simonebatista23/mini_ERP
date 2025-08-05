<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class Database
{
    private static $conn;

    public static function conectar()
    {
        if (!isset(self::$conn)) {
            try {
                $host = $_ENV['DB_HOST'];
                $dbname = $_ENV['DB_NAME'];
                $user = $_ENV['DB_USER'];
                $pass = $_ENV['DB_PASS'];

                self::$conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$conn->exec("SET NAMES 'utf8mb4'");
            } catch (PDOException $e) {
                die("Erro na conexão: " . $e->getMessage());
            }
        }
        return self::$conn;
    }
}
