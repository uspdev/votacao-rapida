<?php

if (empty($argv[1])) {
    echo "Uso: php $argv[0] <codpes>", PHP_EOL;
    echo "Uso: php $argv[0] <codpes> id", PHP_EOL;
    exit;
}

require_once __DIR__ . '/../app/bootstrap.php';

use Uspdev\Votacao\View\Api;

$endpoint = '/gerente/sessao/'.$argv[2].'?codpes=' . $argv[1];
echo 'GET ', API . $endpoint;
$sessao = Api::send($endpoint);

if (!empty($sessoes->status)) {
    echo PHP_EOL;
    print_r($sessao);
    exit;
}

print_r($sessao);
exit;