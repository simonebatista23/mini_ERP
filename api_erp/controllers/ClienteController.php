<?php
require_once __DIR__ . '/../models/Cliente.php';

class ClienteController
{
    private $clienteModel;

    public function __construct()
    {
        $this->clienteModel = new Cliente();
    }

    public function cadastrar()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido. Use POST.']);
            return;
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        if (
            empty($dados['nome']) ||
            empty($dados['email']) ||
            empty($dados['senha'])
        ) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nome, email e senha são obrigatórios.']);
            return;
        }

        $resultado = $this->clienteModel->cadastrar($dados);

        if (isset($resultado['erro'])) {
            http_response_code(400);
            echo json_encode($resultado);
        } else {
            echo json_encode(['sucesso' => 'Cliente cadastrado com sucesso!']);
        }
    }
}
