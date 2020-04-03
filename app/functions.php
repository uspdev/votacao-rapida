<?php
use \RedBeanPHP\R as R;

function generateRandomString($length = 6)
{
    return substr(str_shuffle(str_repeat($x = 'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
}

function gerarTokens($qt, $import = false)
{
    $tokens = [];
    foreach (['apoio', 'tela', 'recepcao'] as $tipo) {
        if ($import) {
            $token['_type'] = 'token';
        }
        $token['tipo'] = $tipo;
        $token['token'] = generateRandomString(6);
        $tokens[] = $token;
    }

    for ($i = 0; $i < $qt; $i++) {
        if ($import) {
            $token['_type'] = 'token';
        }
        $token['tipo'] = 'votacao';
        $token['token'] = generateRandomString(6);
        $tokens[] = $token;
    }
    return $tokens;
}

function obterSessao($hash, $token)
{
    global $base;
    $auth = base64_encode("admin:admin");
    $get_context = stream_context_create([
        "http" => [
            "header" => "Authorization: Basic $auth",
        ],
    ]);

    $w = file_get_contents($base . '/'.$hash.'/' . $token, false, $get_context);
    if (!$w) {
        echo 'Sem sessao para esse token', PHP_EOL;
        exit;
    }
    return json_decode($w);
}

function obterTokenApoio($hash)
{
    $sessao = R::findOne('sessao', 'hash = ?', [$hash]);
    $token = R::findOne('token', "tipo = 'apoio' and sessao_id = ? ", [$sessao->id]);
    return $token->token;
}

function post($hash, $token, $data)
{
    global $base;
    $auth = base64_encode("admin:admin");
    $context = stream_context_create([
        "http" => [
            'method' => 'POST',
            "header" => [
                "Authorization: Basic $auth",
                'Content-Type: application/json',
                'user-agent: mock data votacao v1.0',
            ],
            'content' => json_encode($data),
        ],
    ]);

    $w = file_get_contents($base . '/' . $hash . '/' . $token, false, $context);
    return json_decode($w);
}
