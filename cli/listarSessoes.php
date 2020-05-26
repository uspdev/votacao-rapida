<?php

if (empty($argv[1])) {
    echo "Uso: php $argv[0] <codpes>", PHP_EOL;
    echo "Uso: php $argv[0] <codpes> show", PHP_EOL;
    exit;
}

require_once __DIR__ . '/../app/bootstrap.php';

use Uspdev\Votacao\View\Api;

$endpoint = '/gerente/listarSessoes?codpes=' . $argv[1];
echo 'GET ', API . $endpoint;
$sessoes = Api::send($endpoint);

if (!empty($sessoes->status)) {
    echo PHP_EOL;
    print_r($sessoes);
    exit;
}

if (!empty($sessoes)) {
    echo ' ok', PHP_EOL;
    echo 'Retornou: ' . count((array)$sessoes) . ' registros', PHP_EOL;
    echo "Use php $argv[0] <codpes> show, para mostrar a saída", PHP_EOL;
}

if (!empty($argv[2]) && $argv[2] == 'show') {
    echo PHP_EOL;
    foreach ($sessoes as $sessao) {
        echo json_encode($sessao, JSON_UNESCAPED_UNICODE), PHP_EOL;
    }
}
