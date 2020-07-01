-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 01/07/2020 às 10:06
-- Versão do servidor: 10.1.44-MariaDB-0ubuntu0.18.04.1
-- Versão do PHP: 7.2.24-0ubuntu0.18.04.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `votacao`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `acao`
--

CREATE TABLE `acao` (
  `id` int(11) UNSIGNED NOT NULL,
  `cod` int(11) UNSIGNED DEFAULT NULL,
  `estado` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `msg` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `escopo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alternativa`
--

CREATE TABLE `alternativa` (
  `id` int(11) UNSIGNED NOT NULL,
  `texto` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `votacao_id` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email`
--

CREATE TABLE `email` (
  `id` int(11) UNSIGNED NOT NULL,
  `destinatario` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bcc` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assunto` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `corpo` text COLLATE utf8mb4_unicode_ci,
  `alt` text COLLATE utf8mb4_unicode_ci,
  `responder_para` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `embedded` text COLLATE utf8mb4_unicode_ci,
  `enviado` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sessao_id` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estado`
--

CREATE TABLE `estado` (
  `id` int(11) UNSIGNED NOT NULL,
  `cod` int(11) UNSIGNED DEFAULT NULL,
  `nome` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acoes` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tabela` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `resposta`
--

CREATE TABLE `resposta` (
  `id` int(11) UNSIGNED NOT NULL,
  `votacao_id` int(11) UNSIGNED DEFAULT NULL,
  `alternativa_id` int(11) UNSIGNED DEFAULT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `dispositivo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last` int(11) UNSIGNED DEFAULT NULL,
  `apelido` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessao`
--

CREATE TABLE `sessao` (
  `id` int(11) UNSIGNED NOT NULL,
  `unidade` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ano` int(11) UNSIGNED DEFAULT NULL,
  `nome` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hash` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_manual` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arq_tokens_pdf` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lista` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tokens_pdf` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomes_json` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quando` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_fechado` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_direto` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessao_usuario`
--

CREATE TABLE `sessao_usuario` (
  `id` int(11) UNSIGNED NOT NULL,
  `usuario_id` int(11) UNSIGNED DEFAULT NULL,
  `sessao_id` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `token`
--

CREATE TABLE `token` (
  `id` int(11) UNSIGNED NOT NULL,
  `tipo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sessao_id` int(11) UNSIGNED DEFAULT NULL,
  `nome` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` int(11) UNSIGNED DEFAULT NULL,
  `apelido` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticket` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) UNSIGNED NOT NULL,
  `codpes` int(11) UNSIGNED DEFAULT NULL,
  `nome` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidade` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastlogin` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin` tinyint(1) UNSIGNED DEFAULT NULL,
  `ultimo_aviso` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `votacao`
--

CREATE TABLE `votacao` (
  `id` int(11) UNSIGNED NOT NULL,
  `estado` int(11) UNSIGNED DEFAULT NULL,
  `nome` text COLLATE utf8mb4_unicode_ci,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `tipo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_count` int(11) UNSIGNED DEFAULT NULL,
  `data_ini` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_fim` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sessao_id` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices de tabelas apagadas
--

--
-- Índices de tabela `acao`
--
ALTER TABLE `acao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `alternativa`
--
ALTER TABLE `alternativa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_foreignkey_alternativa_votacao` (`votacao_id`);

--
-- Índices de tabela `email`
--
ALTER TABLE `email`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_foreignkey_email_sessao` (`sessao_id`);

--
-- Índices de tabela `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `resposta`
--
ALTER TABLE `resposta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_foreignkey_resposta_votacao` (`votacao_id`),
  ADD KEY `index_foreignkey_resposta_alternativa` (`alternativa_id`);

--
-- Índices de tabela `sessao`
--
ALTER TABLE `sessao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `sessao_usuario`
--
ALTER TABLE `sessao_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UQ_4974cd5aec13b281a9242dc396972a1e2812beeb` (`sessao_id`,`usuario_id`),
  ADD KEY `index_foreignkey_sessao_usuario_usuario` (`usuario_id`),
  ADD KEY `index_foreignkey_sessao_usuario_sessao` (`sessao_id`);

--
-- Índices de tabela `token`
--
ALTER TABLE `token`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_foreignkey_token_sessao` (`sessao_id`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `votacao`
--
ALTER TABLE `votacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_foreignkey_votacao_sessao` (`sessao_id`);

--
-- AUTO_INCREMENT de tabelas apagadas
--

--
-- AUTO_INCREMENT de tabela `acao`
--
ALTER TABLE `acao`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `alternativa`
--
ALTER TABLE `alternativa`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email`
--
ALTER TABLE `email`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estado`
--
ALTER TABLE `estado`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `resposta`
--
ALTER TABLE `resposta`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `sessao`
--
ALTER TABLE `sessao`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `sessao_usuario`
--
ALTER TABLE `sessao_usuario`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `token`
--
ALTER TABLE `token`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `votacao`
--
ALTER TABLE `votacao`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para dumps de tabelas
--

--
-- Restrições para tabelas `alternativa`
--
ALTER TABLE `alternativa`
  ADD CONSTRAINT `c_fk_alternativa_votacao_id` FOREIGN KEY (`votacao_id`) REFERENCES `votacao` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Restrições para tabelas `email`
--
ALTER TABLE `email`
  ADD CONSTRAINT `c_fk_email_sessao_id` FOREIGN KEY (`sessao_id`) REFERENCES `sessao` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Restrições para tabelas `resposta`
--
ALTER TABLE `resposta`
  ADD CONSTRAINT `c_fk_resposta_alternativa_id` FOREIGN KEY (`alternativa_id`) REFERENCES `alternativa` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `c_fk_resposta_votacao_id` FOREIGN KEY (`votacao_id`) REFERENCES `votacao` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Restrições para tabelas `sessao_usuario`
--
ALTER TABLE `sessao_usuario`
  ADD CONSTRAINT `c_fk_sessao_usuario_sessao_id` FOREIGN KEY (`sessao_id`) REFERENCES `sessao` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `c_fk_sessao_usuario_usuario_id` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `token`
--
ALTER TABLE `token`
  ADD CONSTRAINT `c_fk_token_sessao_id` FOREIGN KEY (`sessao_id`) REFERENCES `sessao` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Restrições para tabelas `votacao`
--
ALTER TABLE `votacao`
  ADD CONSTRAINT `c_fk_votacao_sessao_id` FOREIGN KEY (`sessao_id`) REFERENCES `sessao` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;