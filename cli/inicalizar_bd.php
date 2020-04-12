<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

if (!empty($argv[1]) && $argv[1] == 'sim') {
    echo salvarUsuarioApi(), PHP_EOL;
    echo limparBD(), PHP_EOL;
    echo dadosDeControle(), PHP_EOL;
} else {
    echo 'Uso: php inicializar_br.php sim', PHP_EOL;
}
