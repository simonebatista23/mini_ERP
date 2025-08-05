<?php
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../database/base.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/JWTHandler.php';



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class PedidoController
{
    private $conn;
    private $pedido;
    public function __construct()
    {
        $this->conn = Database::conectar();
        $this->pedido = new Pedido();
    }

    public function calcularFrete()
    {
        $pedido = new Pedido();
        $pedido->calcularFreteAPI();
    }

    public function finalizarPedido()
    {

        $carrinho = json_decode(file_get_contents('php://input'), true);

        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            Response::json(["error" => "Token não enviado"], 401);
        }
        $authHeader = $headers['Authorization'];
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::json(["error" => "Formato do token inválido"], 401);
        }
        $token = $matches[1];
        $decoded = JWTHandler::validateToken($token);
        if (!$decoded) {
            Response::json(["error" => "Token inválido ou expirado"], 401);
        }

        if (!isset($carrinho['carrinho']) || !is_array($carrinho['carrinho'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Carrinho inválido ou não enviado']);
            return;
        }
    
        $data = json_decode(json_encode($decoded), true);

        if (empty($data['id'])) {
            Response::json(["error" => "Cliente não identificado"], 400);
            return;
        }

        $pedido = new Pedido();
        $resultado = $pedido->salvar($carrinho);

        if ($resultado === true) {
            try {
                $stmt = $this->conn->prepare("SELECT * FROM clientes WHERE id = ?");
                $stmt->execute([$data['id']]);
                $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($cliente) {
                    $email = $cliente['email'];
                    $nome = $cliente['nome'];
                    $logradouro =     $cliente['logradouro'];
                    $numero =   $cliente['numero'];
                    $bairro =      $cliente['bairro'];
                    $cidade =   $cliente['cidade'];
                    $uf =     $cliente['uf'];

                    $mail = new PHPMailer(true);
                    $mail->CharSet = 'UTF-8';

                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'simoneliveira1224@gmail.com';
                    $mail->Password = 'ntzx uhkd myva pxve';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;

                    $mail->setFrom('simoneliveira1224@gmail.com', 'Montink');
                    $mail->addAddress($email, $nome);
                    $mail->isHTML(true);
                    $mail->Subject = 'Confirmação de Pedido';
                    $mail->Body    = "Olá <b>$nome</b>,<br>Seu pedido foi realizado com sucesso! vai ser entregue no seu endereço <b>$logradouro $numero,  $bairro   $cidade - $uf</b>
                    <br><br>Obrigado por comprar conosco!";

                    $mail->send();
                }
            } catch (Exception $e) {
                error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
            }

            echo json_encode(['sucesso' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao salvar pedido', 'detalhe' => $resultado]);
        }
    }

    public function listarPedidosUsuario()
    {
        header('Content-Type: application/json');

        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Token não enviado']);
            return;
        }
        $authHeader = $headers['Authorization'];
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Formato do token inválido']);
            return;
        }
        $token = $matches[1];
        $decoded = JWTHandler::validateToken($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido ou expirado']);
            return;
        }

        $data = json_decode(json_encode($decoded), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Cliente não identificado']);
            return;
        }

        $cliente_id = $data['id'];

        $pedido = new Pedido();
        $pedidos = $pedido->listarPorCliente($cliente_id);

        echo json_encode($pedidos);
    }

    public function listarTodosPedidos()
    {
        header('Content-Type: application/json');

        try {
            $pedidos = $this->pedido->listarTodos();
            echo json_encode($pedidos);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao listar pedidos', 'detalhes' => $e->getMessage()]);
        }
    }
}
