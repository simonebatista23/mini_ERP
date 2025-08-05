<?php
class Carrinho
{
    public static function adicionar($produtoId, $nome, $variacao, $preco, $quantidade)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }


        foreach ($_SESSION['carrinho'] as &$item) {
            if ($item['produto_id'] == $produtoId && $item['variacao'] == $variacao) {
                $item['quantidade'] += $quantidade;
                $item['subtotal'] = $item['quantidade'] * $preco;
                return;
            }
        }

        $_SESSION['carrinho'][] = [
            'produto_id' => $produtoId,
            'nome' => $nome,
            'variacao' => $variacao,
            'preco_unitario' => $preco,
            'quantidade' => $quantidade,
            'subtotal' => $preco * $quantidade
        ];
    }

    public static function remover($produtoId, $variacao)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['carrinho'])) return;

        foreach ($_SESSION['carrinho'] as $i => $item) {
            if ($item['produto_id'] == $produtoId && $item['variacao'] == $variacao) {
                unset($_SESSION['carrinho'][$i]);
                $_SESSION['carrinho'] = array_values($_SESSION['carrinho']);
                return;
            }
        }
    }


    public static function removerItem($produtoId, $variacao, $qtd = 1)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['carrinho'])) {
            return false;
        }

        $produtoId = (int)$produtoId;
        $variacao  = trim((string)$variacao);

        foreach ($_SESSION['carrinho'] as $i => &$item) {
            $idItem  = (int)$item['produto_id'];
            $varItem = trim((string)$item['variacao']);

            if ($idItem === $produtoId && $varItem === $variacao) {
                $item['quantidade'] -= (int)$qtd;

                if ($item['quantidade'] <= 0) {
                    unset($_SESSION['carrinho'][$i]);
                    $_SESSION['carrinho'] = array_values($_SESSION['carrinho']);
                    return 0;
                }

                $item['subtotal'] = $item['quantidade'] * $item['preco_unitario'];
                return $item['quantidade'];
            }
        }

        return false;
    }


    public static function listar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['carrinho'] ?? [];
    }

    public static function limpar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['carrinho'] = [];
    }
}
