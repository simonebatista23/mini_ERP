<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler
{
    private static $secret_key;
    private static $algorithm;
    public static function init()
    {
        self::$secret_key = $_ENV['JWT_SECRET_KEY'];
        self::$algorithm = $_ENV['JWT_ALGORITHM'];
    }
    public static function generateToken($payload)
    {
        return JWT::encode($payload, self::$secret_key, self::$algorithm);
    }

    public static function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(self::$secret_key, self::$algorithm));
            return (array) $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
}


























