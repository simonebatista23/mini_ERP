<?php
require_once __DIR__ . '/../database/base.php';

class Produto
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::conectar();
    }


    public function salvar($data)
    {
        $stmt = $this->conn->prepare("INSERT INTO produtos (nome, preco) VALUES (:nome, :preco)");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':preco' => $data['preco']
        ]);

        $produto_id = $this->conn->lastInsertId();

        foreach ($data['variacao'] as $index => $var) {
            $quant = $data['quantidade'][$index];
            $stmtEstoque = $this->conn->prepare("INSERT INTO estoques (produto_id, variacao, quantidade) VALUES (:produto_id, :variacao, :quantidade)");
            $stmtEstoque->execute([
                ':produto_id' => $produto_id,
                ':variacao' => $var,
                ':quantidade' => $quant
            ]);
        }

        return true;
    }

    public function atualizar($data)
    {
        $stmt = $this->conn->prepare("UPDATE produtos SET nome = :nome, preco = :preco WHERE id = :id");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':preco' => $data['preco'],
            ':id' => $data['id']
        ]);

        foreach ($data['variacao'] as $index => $var) {
            $quant = $data['quantidade'][$index];

            $stmtCheck = $this->conn->prepare("SELECT id FROM estoques WHERE produto_id = :produto_id AND variacao = :variacao");
            $stmtCheck->execute([
                ':produto_id' => $data['id'],
                ':variacao' => $var
            ]);

            if ($stmtCheck->fetch()) {
                $stmtUpdate = $this->conn->prepare("UPDATE estoques SET quantidade = :quantidade WHERE produto_id = :produto_id AND variacao = :variacao");
                $stmtUpdate->execute([
                    ':produto_id' => $data['id'],
                    ':variacao' => $var,
                    ':quantidade' => $quant
                ]);
            } else {
                $stmtInsert = $this->conn->prepare("INSERT INTO estoques (produto_id, variacao, quantidade) VALUES (:produto_id, :variacao, :quantidade)");
                $stmtInsert->execute([
                    ':produto_id' => $data['id'],
                    ':variacao' => $var,
                    ':quantidade' => $quant
                ]);
            }
        }

        return true;
    }

    public function adicionarAoCarrinho($data)
    {
        $item = [
            'id' => $data['id'],
            'nome' => $data['nome'],
            'variacao' => $data['variacao'][0],
            'preco' => $data['preco'],
            'quantidade' => $data['quantidade'][0],
            'subtotal' => $data['preco'] * $data['quantidade'][0]
        ];
        $_SESSION['carrinho'][] = $item;
    }

    public function listarProdutos()
    {
        $sql = "SELECT 
                p.id AS produto_id, 
                p.nome, 
                p.preco, 
                e.variacao, 
                e.quantidade
            FROM produtos p 
            JOIN estoques e ON p.id = e.produto_id";

        $stmt = $this->conn->query($sql);
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($produtos);
    }
}
