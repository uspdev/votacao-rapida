<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

use \RedBeanPHP\R as R;

$sessoes = R::findAll('sessao');
foreach ($sessoes as $sessao) {
    echo $sessao->id,': ',$sessao->nome.': '.$sessao->hash,PHP_EOL;
}

