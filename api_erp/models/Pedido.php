<?php
require_once __DIR__ . '/../database/base.php';

class Pedido
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::conectar();
    }

    public function salvar($data)
    {
        $conn = $this->conn;

        if (empty($data['cliente_id'])) {
            return "Cliente não identificado.";
        }
        $cliente_id = $data['cliente_id'];


        $stmtCliente = $conn->prepare("SELECT cep, logradouro, numero, bairro, cidade, uf FROM clientes WHERE id = ?");
        $stmtCliente->execute([$cliente_id]);
        $clienteEndereco = $stmtCliente->fetch();

        if (!$clienteEndereco) {
            return "Cliente não encontrado.";
        }

        $carrinho = $data['carrinho'];
        $subtotal = 0;


        foreach ($carrinho as $item) {
            $stmtEstoque = $conn->prepare("SELECT quantidade FROM estoques WHERE produto_id = ? AND variacao = ? LIMIT 1");
            $stmtEstoque->execute([$item['produto_id'], $item['variacao']]);
            $estoque = $stmtEstoque->fetch();

            if (!$estoque || $estoque['quantidade'] < $item['quantidade']) {
                return "Estoque insuficiente para o produto ID {$item['produto_id']} variação '{$item['variacao']}'";
            }

            $subtotal += $item['preco_unitario'] * $item['quantidade'];
        }

        if ($subtotal >= 200) {
            $frete = 0;
        } elseif ($subtotal >= 52) {
            $frete = 15;
        } else {
            $frete = 20;
        }

        $desconto = 0;
        $cupomCodigo = $data['cupom'] ?? null;

        if ($cupomCodigo) {
            $stmtCupom = $conn->prepare("SELECT * FROM cupons WHERE codigo = ? AND validade >= CURDATE() AND ativo = 1");
            $stmtCupom->execute([$cupomCodigo]);
            $cupom = $stmtCupom->fetch();

            if (!$cupom) return "Cupom inválido, expirado ou não encontrado.";
            if ($subtotal < $cupom['valor_minimo']) return "Subtotal insuficiente para aplicar este cupom.";

            $desconto = $cupom['desconto_fixo'] ?? 0;
        }

        $total = $subtotal + $frete - $desconto;

        $stmtPedido = $conn->prepare("
            INSERT INTO pedidos 
            (cliente_id, frete, subtotal, total, desconto, cupom_codigo, cep, logradouro, numero, bairro, cidade, uf)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtPedido->execute([
            $cliente_id,
            $frete,
            $subtotal,
            $total,
            $desconto,
            $cupomCodigo,
            $clienteEndereco['cep'],
            $clienteEndereco['logradouro'],
            $clienteEndereco['numero'],
            $clienteEndereco['bairro'],
            $clienteEndereco['cidade'],
            $clienteEndereco['uf'],
        ]);

        $pedido_id = $conn->lastInsertId();

        foreach ($carrinho as $item) {
            $stmtItem = $conn->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, variacao, quantidade, preco_unitario) VALUES (?, ?, ?, ?, ?)");
            $stmtItem->execute([
                $pedido_id,
                $item['produto_id'],
                $item['variacao'],
                $item['quantidade'],
                $item['preco_unitario']
            ]);
        }

        return true;
    }

    public function calcularFreteAPI()
    {
        header('Content-Type: application/json');

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        $conn = $this->conn;

        if (!is_array($data) || !isset($data['carrinho']) || !is_array($data['carrinho'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Carrinho inválido ou não enviado.']);
            return;
        }

        $subtotal = 0.0;
        $carrinho = $data['carrinho'];

        foreach ($carrinho as $item) {

            if (
                !isset($item['produto_id']) ||
                !isset($item['variacao']) ||
                !isset($item['quantidade'])
            ) {
                http_response_code(400);
                echo json_encode(['erro' => 'Item de carrinho incompleto.']);
                return;
            }

            $stmtEstoque = $conn->prepare("
            SELECT quantidade 
            FROM estoques 
            WHERE produto_id = ? AND variacao = ? 
            LIMIT 1
        ");
            $stmtEstoque->execute([$item['produto_id'], $item['variacao']]);
            $estoque = $stmtEstoque->fetch(PDO::FETCH_ASSOC);

            if (!$estoque || (int)$estoque['quantidade'] < (int)$item['quantidade']) {
                http_response_code(409);
                echo json_encode([
                    'erro'        => 'Estoque insuficiente',
                    'produto_id'  => $item['produto_id'],
                    'variacao'    => $item['variacao'],
                    'solicitado'  => (int)$item['quantidade'],
                    'disponivel'  => $estoque ? (int)$estoque['quantidade'] : 0,
                ]);
                return;
            }

            $precoUnit = isset($item['preco_unitario']) ? (float)$item['preco_unitario'] : 0.0;
            $subtotal += $precoUnit * (int)$item['quantidade'];
        }

        if ($subtotal >= 200) {
            $frete = 0;
        } elseif ($subtotal >= 52) {
            $frete = 15;
        } else {
            $frete = 20;
        }

        http_response_code(200);
        echo json_encode([
            'subtotal' => $subtotal,
            'frete'    => $frete,
        ]);
    }

    public function removerPedido($id)
    {
        $stmtItens = $this->conn->prepare("DELETE FROM pedido_itens WHERE pedido_id = ?");
        $stmtItens->execute([$id]);
        $stmtPedido = $this->conn->prepare("DELETE FROM pedidos WHERE id = ?");
        $stmtPedido->execute([$id]);
    }


    public function atualizarStatus($id, $status)
    {
        $stmt = $this->conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public function listarPorCliente($cliente_id)
    {
        $stmt = $this->conn->prepare("SELECT id, status, total, criado_em FROM pedidos WHERE cliente_id = ? ORDER BY criado_em DESC");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodos()
    {
        $stmt = $this->conn->prepare("
        SELECT 
            p.id, 
            p.cliente_id, 
            c.nome AS cliente_nome, 
            c.email AS cliente_email,
            p.status, 
            p.total, 
            p.criado_em
        FROM pedidos p
        JOIN clientes c ON c.id = p.cliente_id
        ORDER BY p.criado_em DESC
    ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
