CREATE DATABASE `mini_erp_teste` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
use mini_erp_teste ;

CREATE TABLE `produtos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `estoques` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produto_id` int NOT NULL,
  `variacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantidade` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `estoques_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cep` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `logradouro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uf` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cupons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_minimo` decimal(10,2) DEFAULT NULL,
  `validade` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `desconto_fixo` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `frete` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `cep` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pago','excluido','cancelado','pendente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `cupom_codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desconto` decimal(10,2) DEFAULT '0.00',
  `logradouro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uf` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pedidos_clientes` (`cliente_id`),
  CONSTRAINT `fk_pedidos_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pedido_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `produto_id` int NOT NULL,
  `variacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantidade` int NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `pedido_itens_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pedido_itens_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$


DROP TRIGGER IF EXISTS `trg_verificar_abater_estoque` $$
CREATE TRIGGER `trg_verificar_abater_estoque`
BEFORE INSERT ON `pedido_itens`
FOR EACH ROW
BEGIN
  UPDATE `estoques`
     SET quantidade = quantidade - NEW.quantidade
   WHERE produto_id = NEW.produto_id
     AND (
           (variacao IS NULL AND NEW.variacao IS NULL)
        OR variacao = NEW.variacao
         )
     AND quantidade >= NEW.quantidade;

  IF ROW_COUNT() = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Estoque inexistente ou insuficiente para o item.';
  END IF;
END $$


DROP TRIGGER IF EXISTS `trg_devolver_estoque` $$
CREATE TRIGGER `trg_devolver_estoque`
BEFORE DELETE ON `pedido_itens`
FOR EACH ROW
BEGIN
  UPDATE `estoques`
     SET quantidade = quantidade + OLD.quantidade
   WHERE produto_id = OLD.produto_id
     AND (
           (variacao IS NULL AND OLD.variacao IS NULL)
        OR variacao = OLD.variacao
         );
END $$



DELIMITER ;
SHOW TRIGGERS;
