<?php

function generateRandomString($length = 6)
{
    return substr(str_shuffle(str_repeat($x = 'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
}

function gerarTokens($qt, $import = false)
{
    // o import = true gera um campo _type usado pelo
    // redbean para importar com dispense
    $tipos = [
        ['tipo' => 'apoio', 'qt' => 1],
        ['tipo' => 'tela', 'qt' => 1],
        ['tipo' => 'recepcao', 'qt' => 1],
        ['tipo' => 'fechada', 'qt' => $qt],
        ['tipo' => 'aberta', 'qt' => $qt],
    ];

    $tokens = [];
    foreach ($tipos as $tipo) {
        if ($import) {
            $token['_type'] = 'token';
        }
        $token['tipo'] = $tipo['tipo'];
        for ($i = 0; $i < $tipo['qt']; $i++) {
            // aqui vamos garantir tokens únicos para cada sessão
            while ($newToken = generateRandomString(6)) {
                if (!in_array($newToken, array_column($tokens, 'token'))) {
                    $token['token'] = $newToken;
                    break;
                }
                //echo 'gerou repetido! ', $newToken,PHP_EOL;exit;
            }
            $tokens[] = $token;
        }
    }
    return $tokens;
}

function obterSessao($hash, $token)
{
    $url = getenv('USPDEV_VOTACAO_API') . '/run/' . $hash . '/' . $token;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, 'admin:admin');
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $return = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($return);
    return $json ? $json : $return;
}

function post($hash, $token, $data)
{
    $url = getenv('USPDEV_VOTACAO_API') . '/run/' . $hash . '/' . $token;
    $headers = ['Content-Type: application/json', 'user-agent: mock data votacao v1.0'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, 'admin:admin');
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $return = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($return);
    return $json ? $json : $return;
}
