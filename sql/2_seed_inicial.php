<?php
// 
// SEED INICIAL dados de controle

require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;
R::freeze(false);

echo 'Criando usuário da API.. ';
Uspdev\Webservice\Auth::salvarUsuario(
    [
        'username' => getenv('API_USER'),
        'pwd' => getenv('API_PWD'),
        'admin' => '1',
        'allow' => ''
    ]
);
echo 'OK.', PHP_EOL;

R::selectDatabase('votacao');

echo 'Cadastrando primeiro admin.. ';
$codpes = getenv('ADMIN_CODPES');
$usuario = R::findOrCreate('usuario', ['codpes' => $codpes,]);
$usuario->admin = true;
$usuario->nome = 'admin';
$usuario->email = 'admin@local.com';
$usuario->unidade = 'EESC';
$usuario->lastlogin = date('Y-m-d H:i:s');
$usuario->ultimo_aviso = 0;
R::store($usuario);
echo 'OK.',PHP_EOL;

echo 'Seed de dados de estado e acao.. ';
if ($estados = R::findAll('estado') or $acoes = R::findAll('acao')) {
    echo 'tabelas não estão vazias: abortando.', PHP_EOL;
    exit;
}

$estados = [
    ['_type' => 'estado', 'cod' => 0, 'nome' => 'Fechada', 'acoes' => '0, 10', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 1, 'nome' => 'Em exibição', 'acoes' => '1,2', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 2, 'nome' => 'Em votação', 'acoes' => '3', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 3, 'nome' => 'Em pausa', 'acoes' => '4,5,6', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 4, 'nome' => 'Resultado', 'acoes' => '7', 'tabela' => 'votacao'],
    ['_type' => 'estado', 'cod' => 5, 'nome' => 'Finalizado', 'acoes' => '4', 'tabela' => 'votacao'],
];
foreach ($estados as $e) {
    R::store(R::dispense($e));
}

$acoes = [
    ['_type' => 'acao', 'cod' => 0, 'estado' => '1', 'nome' => 'Mostrar', 'msg' => 'Votação em tela', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 1, 'estado' => '0', 'nome' => 'Esconder', 'msg' => 'Votação fechada', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 2, 'estado' => '2', 'nome' => 'Iniciar', 'msg' => 'Votação aberta', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 3, 'estado' => '3', 'nome' => 'Pausar', 'msg' => 'Votação pausada', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 4, 'estado' => '4', 'nome' => 'Mostrar resultado', 'msg' => 'Mostrando resultados', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 5, 'estado' => '2', 'nome' => 'Continuar', 'msg' => 'Votação aberta', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 6, 'estado' => '1', 'nome' => 'Reiniciar', 'msg' => 'Votação reiniciada', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 7, 'estado' => '5', 'nome' => 'Finalizar', 'msg' => 'Votação finalizada', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 8, 'estado' => '', 'nome' => 'Responder', 'msg' => 'Resposta aceita', 'escopo' => 'votacao'],
    ['_type' => 'acao', 'cod' => 9, 'estado' => '', 'nome' => 'Instantâneo', 'msg' => 'Novo instantâneo', 'escopo' => 'apoio'],
    ['_type' => 'acao', 'cod' => 10, 'estado' => '', 'nome' => 'Excluir', 'msg' => 'Excluido com sucesso', 'escopo' => 'apoio'],
];
foreach ($acoes as $a) {
    R::store(R::dispense($a));
}

echo 'OK', PHP_EOL;
