<?php
// Issue #71 - Implementação de ordenação das votações
// Criar coluna no BD
// atualizar registros existentes

require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::freeze(false);

echo 'Criando coluna no BD.. ';
try {
    R::exec('ALTER TABLE `votacao` ADD `ordem` INT(11) UNSIGNED;');
    echo 'OK', PHP_EOL;

    echo 'Atualizando registros existentes (pode demorar um pouco).. ';
    $sessoes = R::findAll('sessao');
    foreach ($sessoes as $sessao) {
        $votacoes = $sessao->ownVotacaoList;
        $ordem = 1;
        foreach ($votacoes as $votacao) {
            $votacao->ordem = $ordem;
            R::store($votacao);
            $ordem++;
        }
        echo '.';
    }
    echo 'OK', PHP_EOL;
} catch (\Exception $e) {
    echo 'AVISO: coluna já existe.', PHP_EOL;
}
