<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

if (!empty($argv[1])) {
    echo importareSubstituirDadosDeSessao($argv[1]), PHP_EOL;
} else {
    echo 'Uso: php importar.php <arquivo>', PHP_EOL;
}
