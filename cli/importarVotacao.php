<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

if (!empty($argv[1])) {
    echo importarVotacao($argv[1], $argv[2]), PHP_EOL;
} else {
    echo 'Uso: php importarVotacao.php <hash> <arquivo>', PHP_EOL;
}
