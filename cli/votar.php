<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../cli/funcoes_cli.php';

echo 'precisamos de um hash para gerar votos aleatórios';exit;

$hash = '';
gerarVotosAleatorios($hash);
