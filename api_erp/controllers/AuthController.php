<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Admin.php';

use Firebase\JWT\JWT;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class AuthController
{
    public function login()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['senha'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Email e senha obrigatórios.']);
            return;
        }

        $clienteModel = new Cliente();
        $cliente = $clienteModel->buscarPorEmail($data['email']);

        if ($cliente && password_verify($data['senha'], $cliente['senha'])) {


            $payload = [
                "id" => $cliente['id'],
                "email" => $cliente['email'],
                "role" => "cliente",
                "exp" => time() + (60 * 60 * 24)
            ];

            $token = JWTHandler::generateToken($payload);
            Response::json([
                "message" => "Login realizado com sucesso",
                "token" => $token
            ]);
            return;
        }

        http_response_code(401);
        echo json_encode(['erro' => 'Credenciais inválidas.']);
    }

    public function loginAdmin()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['email']) || empty($data['senha'])) {
            echo "sem admin";
        }

        $adminModel = new Admin();
        $admin =  $adminModel->buscarEmailAdmin(($data['email']));
 
        if ($admin && password_verify($data['senha'], $admin['senha'])) {

            $payload = [
                'id' => $admin['id'],
                'email' => $admin['email'],
                'role' => 'superAdmin',
                'exp' => time() + (60 * 60 * 24)
            ];

            $token = JWTHandler::generateToken($payload);

            Response::json([
                "messagem" => "admin sucess",
                "token" => $token
            ]);
            return;
        }

        http_response_code(401);
        Response::json([
            "erro" => "credenciais invalidas",
        ]);
    }
}
