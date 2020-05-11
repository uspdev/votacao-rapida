<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

if (!empty($argv[1])) {
    echo salvarAdmin(1575309), PHP_EOL;
} else {
    echo 'Uso: php salvar_admin.php <codpes>', PHP_EOL;
}
