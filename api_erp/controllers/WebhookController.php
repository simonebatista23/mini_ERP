<?php
require_once __DIR__ . '/../models/Pedido.php';

class WebhookController
{
    private $pedido;

    public function __construct()
    {
        $this->pedido = new Pedido();
    }

    public function receberWebhook()
    {
        header('Content-Type: application/json');

        $dados = json_decode(file_get_contents('php://input'), true);

        if (!isset($dados['id']) || !isset($dados['status'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID e status são obrigatórios.']);
            return;
        }

        $id = $dados['id'];
        $status = strtolower(trim($dados['status']));

        try {
            if ($status === 'cancelado') {
                $this->pedido->removerPedido($id);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Pedido cancelado e removido.']);
            } else {
                $this->pedido->atualizarStatus($id, $status);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao processar webhook', 'detalhes' => $e->getMessage()]);
        }
    }
}
