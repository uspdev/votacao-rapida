<?php

namespace Uspdev\Votacao;

class Curl
{
    public static function get($hash, $token)
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
        $sessao = curl_exec($ch);
        curl_close($ch);

        $obj = json_decode($sessao);
        if (!is_object($obj)) {
            echo 'Mensagem ao tentar obter sessão: ', $sessao;
            exit;
        }

        return $obj;
    }

    public static function post($hash, $token, $data)
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
}
