<?php
// Issue #54 - Adicionando campo de observação pos votação
// Criar coluna no BD

require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::freeze(false);

echo 'Criando campo no BD.. ';
try {
    R::exec('ALTER TABLE `votacao` ADD `obs` TEXT NULL DEFAULT NULL;');
    echo 'OK', PHP_EOL;

} catch (\Exception $e) {
    echo 'AVISO: campo votacao.obs já existe.', PHP_EOL;
}

//ALTER TABLE `votacao` ADD `obs`  VARCHAR(191)  ;
