<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once ROOTDIR . '/cli/funcoes_cli.php';

echo salvarUsuarioApi(), PHP_EOL;
echo limparBD(), PHP_EOL;
echo dadosDeControle(), PHP_EOL;
$hash = generateRandomString(20);
echo dadosDeExemplo($hash), PHP_EOL;
//echo gerarPDF($hash), PHP_EOL;
