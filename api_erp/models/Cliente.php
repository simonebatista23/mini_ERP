<?php
require_once __DIR__ . '/../database/base.php';

class Cliente
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::conectar();
    }

    public function cadastrar($dados)
    {

        $stmt = $this->conn->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->execute([$dados['email']]);
        if ($stmt->fetch()) {
            return ['erro' => 'Email já cadastrado.'];
        }


        $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("
            INSERT INTO clientes (nome, email, senha, cep, logradouro, numero, bairro, cidade, uf)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $sucesso = $stmt->execute([
            $dados['nome'],
            $dados['email'],
            $senhaHash,
            $dados['cep'] ?? null,
            $dados['logradouro'] ?? null,
            $dados['numero'] ?? null,
            $dados['bairro'] ?? null,
            $dados['cidade'] ?? null,
            $dados['uf'] ?? null,
        ]);

        if ($sucesso) {
            return ['sucesso' => true];
        } else {
            return ['erro' => 'Erro ao cadastrar cliente.'];
        }
    }
    public function buscarPorEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM clientes WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
