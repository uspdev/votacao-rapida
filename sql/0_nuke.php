<?php
// NUKE do BD
// Para testes
use \RedBeanPHP\R as R;
require_once __DIR__ . '/../app/bootstrap.php';

if (!empty($argv[1]) && $argv[1] == 'sim') {
    R::freeze(false);
    R::selectDatabase('votacao');
    echo 'NUKE: ';
    R::nuke();
    echo 'OK', PHP_EOL;
} else {
    echo 'Este comando apagará todas as tabelas e dados do BD. É isso mesmo que você quer?', PHP_EOL;
    echo 'Conexão: ', getenv('DB_DSN'), PHP_EOL;
    echo 'Uso: php 0-nuke.php sim', PHP_EOL;
    exit;
}
