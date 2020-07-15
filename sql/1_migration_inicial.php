<?php
// Desenvolvido usando MYSQL/MARIADB. Pode ser necessário adaptar para outro BD
// Estrutura inicial
// Cria tabelas
// Cria indices e chaves estrangeiras

require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::freeze(false);
//R::nuke();
echo 'Usando ', getenv('DB_DSN'), PHP_EOL;
echo 'Criando estrutura inicial.. ';

if ($tables = R::inspect()) {
    echo 'BD não está vazio: abortando.', PHP_EOL;
    exit;
}

$sql = file_get_contents(__DIR__ . '/sql1_migration_inicial.sql');

try {
    R::exec($sql);
    echo 'OK', PHP_EOL;
} catch (\Exception $e) {
    echo 'Excessão: ', $e->getMessage(), PHP_EOL;
}
