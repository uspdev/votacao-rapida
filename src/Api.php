<?php

namespace Uspdev\Votacao;

class Api
{
    public static function obterSessao($hash, $token)
    {
        $endpoint = '/run/' . $hash . '/' . $token;
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

        // vamos coletar informaçoes de debug, caso precise
        // https://stackoverflow.com/questions/3757071/php-debugging-curl
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // timeout

        // get ou post
        if (!empty($postdata)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $ret = curl_exec($ch);
        curl_close($ch);
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        $obj = json_decode($ret);
        if (!is_object($obj)) {
            echo '<pre>';
            echo 'Erro no retorno da API: ', PHP_EOL;
            echo 'endpoint = ', API, $endpoint, PHP_EOL;
            echo 'postdata = ', json_encode($postdata), PHP_EOL;
            echo 'retorno = ', var_dump($ret), PHP_EOL;
            echo 'verboselog = ', $verboseLog, PHP_EOL;
            exit;
        }

        return $obj;
    }
}
