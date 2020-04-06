<?php
// estes dados são de controle da votação e devem existir no banco de dados
// deve ser executado apenas uma vez.

require_once __DIR__ . '/bootstrap.php';
use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::useFeatureSet('latest');

R::wipe('estado');
R::wipe('acao');

$estados = [
    ['_type' => 'estado', 'cod' => 0, 'nome' => 'Fechada', 'acoes' => '0', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 1, 'nome' => 'Em tela', 'acoes' => '1,2', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 2, 'nome' => 'Em votação', 'acoes' => '3', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 3, 'nome' => 'Em pausa', 'acoes' => '4,5,6', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 4, 'nome' => 'Resultado', 'acoes' => '7', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 5, 'nome' => 'Finalizado', 'acoes' => '4', 'tabela' => 'votacao'],
];

$acoes = [
    ['_type' => 'acao', 'cod' => 0, 'estado' => '1', 'nome' => 'Mostrar na tela', 'msg' => 'Votação em tela', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 1, 'estado' => '0', 'nome' => 'Fechar', 'msg' => 'Votação fechada', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 2, 'estado' => '2', 'nome' => 'Iniciar', 'msg' => 'Votação aberta', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 3, 'estado' => '3', 'nome' => 'Pausar', 'msg' => 'Votação pausada', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 4, 'estado' => '4', 'nome' => 'Mostrar resultado', 'msg' => 'Mostrando resultados', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 5, 'estado' => '2', 'nome' => 'Continuar', 'msg' => 'Votação aberta', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 6, 'estado' => '2', 'nome' => 'Reiniciar', 'msg' => 'Votação reiniciada', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 7, 'estado' => '5', 'nome' => 'Finalizar', 'msg' => 'Votação finalizada', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 8, 'estado' => '', 'nome' => 'Responder', 'msg' => 'Resposta aceita', 'escopo' => 'votacao'],

];

foreach ($estados as $e) {
    R::store(R::dispense($e));
}
foreach ($acoes as $a) {
    R::store(R::dispense($a));
}

echo 'Dados inseridos com sucesso' . PHP_EOL;
