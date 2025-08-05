<?php
require_once __DIR__ . '/../database/base.php';

class CupomController
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::conectar();
    }

    public function listarCuponsDisponiveis()
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->conn->prepare("
                SELECT id, codigo, desconto_fixo, valor_minimo, validade, ativo
                FROM cupons 
                WHERE ativo = 1 AND validade >= CURDATE()
            ");
            $stmt->execute();
            $cupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($cupons);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'erro' => 'Erro ao buscar cupons',
                'detalhe' => $e->getMessage()
            ]);
        }
    }

    public function criar($dados)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO cupons (codigo, desconto_fixo, valor_minimo, validade, ativo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $dados['codigo'],
                $dados['desconto_fixo'],
                $dados['valor_minimo'],
                $dados['validade'],
                $dados['ativo'] ?? 1
            ]);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Cupom criado com sucesso.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'erro' => 'Erro ao criar cupom',
                'detalhe' => $e->getMessage()
            ]);
        }
    }

    public function atualizar($id, $dados)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->conn->prepare("
                UPDATE cupons 
                SET codigo = ?, desconto_fixo = ?, valor_minimo = ?, validade = ?, ativo = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $dados['codigo'],
                $dados['desconto_fixo'],
                $dados['valor_minimo'],
                $dados['validade'],
                $dados['ativo'],
                $id
            ]);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Cupom atualizado com sucesso.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'erro' => 'Erro ao atualizar cupom',
                'detalhe' => $e->getMessage()
            ]);
        }
    }

    public function desativar()
    {
        header('Content-Type: application/json');
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);

        if (!isset($dados['id'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID do cupom não fornecido.']);
            return;
        }

        try {
            $stmt = $this->conn->prepare("UPDATE cupons SET ativo = 0 WHERE id = ?");
            $stmt->execute([$dados['id']]);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Cupom desativado com sucesso.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'erro' => 'Erro ao desativar cupom',
                'detalhe' => $e->getMessage()
            ]);
        }
    }
}
