<?php
// Atualiza o nome da ação cod = 7 para 'Esconder resultado'

require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::freeze(false);

echo 'Atualizando rótulo da ação 7 para "Esconder resultado".. ';
try {
    $acao = R::findOne('acao', 'cod = 7');
    if ($acao) {
        $acao->nome = 'Esconder resultado';
        $acao->msg = 'Resultado ocultado da tela';
        R::store($acao);
        echo 'OK', PHP_EOL;
    } else {
        echo 'Ação 7 não encontrada.', PHP_EOL;
    }
} catch (\Exception $e) {
    echo 'Erro: ' . $e->getMessage(), PHP_EOL;
}

