<?php
require_once __DIR__ . '/../models/Produto.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/JWTHandler.php';

class ProdutoController
{
    private $produto;

    public function __construct()
    {
        $this->produto = new Produto();
    }

    public function salvarProduto()
    {

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['nome'], $data['preco'], $data['variacao'], $data['quantidade'])) {
            Response::json(['erro' => 'Dados incompletos ou mal formatados'], 400);
            return;
        }

        $data['variacao'] = (array) $data['variacao'];
        $data['quantidade'] = (array) $data['quantidade'];

        if (count($data['variacao']) !== count($data['quantidade'])) {
            Response::json(['erro' => 'Cada variação deve ter uma quantidade correspondente'], 400);
            return;
        }

        try {
            $this->produto->salvar($data);
            Response::json(['sucesso' => true]);
        } catch (Exception $e) {

            Response::json([
                'erro' => 'Erro ao salvar produto',
                'detalhe' => $e->getMessage()
            ], 500);
        }
    }

    public function atualizarProduto()
    {

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['id'], $data['nome'], $data['preco'], $data['variacao'], $data['quantidade'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados incompletos ou mal formatados']);
            return;
        }

        $data['variacao'] = (array) $data['variacao'];
        $data['quantidade'] = (array) $data['quantidade'];

        if (count($data['variacao']) !== count($data['quantidade'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Cada variação deve ter uma quantidade correspondente']);
            return;
        }

        try {
            $this->produto->atualizar($data);
            echo json_encode(['sucesso' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'erro' => 'Erro ao atualizar produto',
                'detalhe' => $e->getMessage()
            ]);
        }
    }

    public function listarProdutos()
    {
        $this->produto->listarProdutos();
    }
}
