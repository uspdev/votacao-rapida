<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

if (!empty($argv[1])) {
    echo limparRespostas($argv[1]), PHP_EOL;
} else {
    echo 'Uso: php limparRespostas.php HASH', PHP_EOL;
}
