<?php
require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::useFeatureSet('latest');

//R::wipe('resposta');


$hash = 'hash001';
$tokens = listarTokens($hash);

$token = $tokens[0];
echo 'Usando token de votação ', $token['token'], PHP_EOL;

$sessao = obterSessao('hash001', $token['token']);

if (empty($sessao->render_form)) {
    echo 'sem votacao aberta', PHP_EOL;
    exit;
}

foreach ($tokens as $token) {
    $voto = gerarVoto($sessao->render_form);
    print_r($voto);

    $voto = votar($hash, $token, $voto);
    echo 'voto',PHP_EOL;
    print_r($voto);
}
// foreach ($tokens as $token) {
//     $voto = gerarVoto($sessao->render_form);
//     $voto = votar($hash, $token, $voto);
//     print_r($voto);
// }

function votar($hash, $token, $voto)
{
    $api = getenv('USPDEV_VOTACAO_API');

    $auth = base64_encode("admin:admin");
    $context = stream_context_create([
        "http" => [
            'method' => 'POST',
            "header" => [
                "Authorization: Basic $auth",
                'Content-Type: application/json',
                'user-agent: mock data votacao v1.0',
            ],
            'content' => json_encode($voto),
        ],
    ]);
    //echo 'voto xx ',json_encode($voto);

    $w = file_get_contents($api . '/run/'.$hash.'/' . $token['token'], false, $context);
    return json_decode($w);
}

function gerarVoto($votacao)
{
    $ids = listarAlternativas($votacao);
    //array_push($ids, ''); // para voto em branco

    $voto['acao'] = '8';
    $voto['votacao_id'] = $votacao->id;
    $voto['alternativa_id'] = $ids[rand(0, count($ids) - 1)];
    return $voto;
}

function listarTokens($hash)
{
    $sessao = R::findOne('sessao', 'hash = ?', [$hash]);
    $tokens = R::find('token', "sessao_id = ? and tipo = 'votacao'", [$sessao->id]);
    return R::exportAll($tokens);
}

function listarAlternativas($votacao)
{
    $alternativas = $votacao->alternativas;
    $ids = [];
    foreach ($alternativas as $alternativa) {
        array_push($ids, $alternativa->id);
    }
    return $ids;
}
