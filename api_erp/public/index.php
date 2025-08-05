<?php
require_once '../controllers/ProdutoController.php';
require_once '../controllers/PedidoController.php';
require_once '../controllers/CupomController.php';
require_once '../controllers/ClienteController.php';
require_once '../controllers/WebhookController.php';
require_once '../controllers/AuthController.php';
require_once '../models/Carrinho.php';
require_once '../utils/JWTHandler.php';
require_once '../utils/Response.php';

JWTHandler::init();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

$rota = $_GET['rota'] ?? '';
$rota = trim($rota, '/');
$method = $_SERVER['REQUEST_METHOD'];

switch ($rota) {

    case 'login':
        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new AuthController())->login();
        break;

        case 'login-admin':
        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new AuthController())->loginAdmin();
        break;

    case 'salvar-produto':
        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new ProdutoController())->salvarProduto();
        break;

    case 'atualizar-produto':
        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new ProdutoController())->atualizarProduto();
        break;

    case 'listar-produtos':
        if ($method !== 'GET') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new ProdutoController())->listarProdutos();
        break;

    case 'calcular-frete':
        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new PedidoController())->calcularFrete();
        break;

    case 'finalizar-pedido':
        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new PedidoController())->finalizarPedido();
        break;

    case 'listar-pedidos':
        if ($method !== 'GET') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }

        (new PedidoController())->listarTodosPedidos();
        break;

    case 'listar-meus-pedido':
        if ($method !== 'GET') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }

        (new PedidoController())->listarPedidosUsuario();
        break;

    case 'cadastrar-cliente':
        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new ClienteController())->cadastrar();
        break;

    case 'webhook_pedido':
        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new WebhookController())->receberWebhook();
        break;

    case 'cupons-disponiveis':
        if ($method !== 'GET') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new CupomController())->listarCuponsDisponiveis();
        break;

    case 'cupons-criar':
        $data = json_decode(file_get_contents('php://input'), true);

        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }

        (new CupomController())->criar($data);
        break;

    case 'cupons-atualizar':
        $data = json_decode(file_get_contents('php://input'), true);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        $id = $data['id'] ?? null;
        if (!$id) {
            Response::json(["error" => "ID do cupom é obrigatório"], 400);
            break;
        }
        (new CupomController())->atualizar($id, $data);
        break;

    case 'desativar-cupom':
        if ($method !== 'POST') {
            Response::json(["error" => "Método não permitido"], 405);
            break;
        }
        (new CupomController())->desativar();
        break;

    case 'adicionar-carrinho':
        $data = json_decode(file_get_contents('php://input'), true);
        Carrinho::adicionar($data['produto_id'], $data['nome'], $data['variacao'], $data['preco'], $data['quantidade']);
        Response::json(['sucesso' => true, 'carrinho' => Carrinho::listar()]);
        break;

    case 'listar-carrinho':
        echo json_encode(Carrinho::listar());
        break;

    case 'remover-carrinho':
        $data = json_decode(file_get_contents('php://input'), true);
        Carrinho::remover($data['produto_id'], $data['variacao']);
        Response::json(['sucesso' => true, 'carrinho' => Carrinho::listar()]);
        break;

    case 'remover-carrinho-item':
        $data = json_decode(file_get_contents('php://input'), true);
        Carrinho::removerItem($data['produto_id'], $data['variacao']);
        Response::json(['sucesso' => true, 'carrinho' => Carrinho::listar()]);
        break;

    case 'limpar-carrinho':
        Carrinho::limpar();
        Response::json(['sucesso' => true]);
        break;


    default:
        http_response_code(404);
        Response::json(['erro' => 'Rota não encontrada', 'rota' => $rota]);
        break;
}
