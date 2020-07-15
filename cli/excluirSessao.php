<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

if (!empty($argv[1])) {
    require $argv[1];
    echo excluirSessao($hash), PHP_EOL;
} else {
    echo 'Uso: php excluir.php <arquivo>', PHP_EOL;
}
