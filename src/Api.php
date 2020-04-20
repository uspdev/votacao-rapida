<?php

namespace Uspdev\Votacao;

class Api
{
    public static function obterSessao($hash, $token)
    {
        $endpoint = '/run/' . $hash . '/' . $token;
        return SELF::send($endpoint);
    }

    public static function obterSessaoPorId($id)
    {
        $endpoint = '/gerente/sessao/' . $id;
        return SELF::send($endpoint);
    }

    public static function post($hash, $token, $data)
    {
        $endpoint = '/run/' . $hash . '/' . $token;
        return SELF::send($endpoint, $data);
    }

    public static function send($endpoint, $postdata = '')
    {
        $ch = curl_init(API . $endpoint);

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $headers = [
                'Content-Type: application/json',
                'user-agent:' . $_SERVER['HTTP_USER_AGENT']
            ];
        } else {
            $headers = [
                'Content-Type: application/json',
                'user-agent:cli user agent'
            ];
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, getenv('API_USER') . ':' . getenv('API_PWD'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        // get ou post
        if (!empty($postdata)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

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
}
