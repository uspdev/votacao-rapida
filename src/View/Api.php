<?php

namespace Uspdev\Votacao\View;

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

    public static function send($endpoint, $postdata = '', $files = '')
    {

        $ch = curl_init(API . $endpoint);

        // Resolução local em memória para VirtualHost (evita necessidade de /etc/hosts e timeout de NAT)
        $parsed = parse_url(API);
        $host = $parsed['host'] ?? '';
        $scheme = $parsed['scheme'] ?? 'http';
        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);

        if ($host && $host !== 'localhost' && $host !== '127.0.0.1') {
            $resolveIp = getenv('API_RESOLVE_IP') ?: '127.0.0.1';
            if ($resolveIp !== 'none' && $resolveIp !== 'false') {
                curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:{$port}:{$resolveIp}"]);
            }
        }

        if ($scheme === 'https' && (getenv('AMBIENTE') === 'dev' || getenv('API_VERIFY_SSL') === 'false')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        // vamos coletar informaçoes de debug, caso precise
        // https://stackoverflow.com/questions/3757071/php-debugging-curl
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {

            if (!$files) {
                $headers = [
                    'Content-Type: application/json',
                    'user-agent:' . $_SERVER['HTTP_USER_AGENT'],
                ];
            } else {
                $headers = ['user-agent:' . $_SERVER['HTTP_USER_AGENT']];
            }
        } else {
            $headers = [
                'Content-Type: application/json',
                'user-agent:cli user agent'
            ];
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, getenv('API_USER') . ':' . getenv('API_PWD'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // timeout

        // get ou post
        if (!empty($postdata)) {
            // post
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($files) {
                foreach ($files as $key => $file) {
                    $cfile = new \CURLFile($file['tmp_name'], $file['type'], $file['name']);
                    $postdata[$key] = $cfile;
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
            }
        } else {
            // get
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $ret = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        $obj = json_decode($ret);
        if (!is_object($obj)) {
            echo '<pre>', PHP_EOL;
            echo 'Erro no retorno da API (HTTP ', $httpCode, '): ', PHP_EOL;
            echo 'endpoint = ', API, $endpoint, PHP_EOL;
            echo 'postdata = ', json_encode($postdata), PHP_EOL;
            echo 'retorno = ', var_dump($ret), PHP_EOL;
            echo 'verboselog = ', $verboseLog, PHP_EOL;
            exit;
        }

        return $obj;
    }
}
