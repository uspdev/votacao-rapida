<?php
require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::useFeatureSet('latest');

$hash = 'hash001';

// vamos pegar o tipo de votação que estiver 'em votação'
$em_votacao = 2;
$tipo = R::getCell('SELECT tipo FROM votacao WHERE estado = ?', [$em_votacao]);
if (!$tipo) {
    echo 'Sem votação aberta.', PHP_EOL;
    return;
}

// e pegar todos os tokens desse tipo
$sessao_tmp = R::findOne('sessao', 'hash = ?', [$hash]);
$tokens = R::find('token', "sessao_id = ? and tipo = ?", [$sessao_tmp->id, $tipo]);
$tokens = R::exportAll($tokens);

// vamos votar
foreach ($tokens as $token) {
    $sessao = obterSessao($hash, $token['token']);

    $voto = gerarVotoAleatorio($sessao->render_form);
    echo 'Voto enviado do token ', $token['token'], PHP_EOL;
    echo json_encode($voto), PHP_EOL, PHP_EOL;

    $voto = post($hash, $token['token'], $voto);
    echo 'voto recebido', PHP_EOL;
    echo json_encode($voto), PHP_EOL, PHP_EOL;
}

function gerarVotoAleatorio($votacao)
{
    $ids = array_column($votacao->alternativas, 'id');
    $voto['acao'] = '8';
    $voto['votacao_id'] = $votacao->id;
    $voto['alternativa_id'] = $ids[rand(0, count($ids) - 1)];
    return $voto;
}
