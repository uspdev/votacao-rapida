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
            $resolveIp = config('api_resolve_ip');
            if ($resolveIp !== 'none' && $resolveIp !== 'false') {
                curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:{$port}:{$resolveIp}"]);
            }
        }

        if ($scheme === 'https' && (config('is_dev') || !config('api_verify_ssl'))) {
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
        curl_setopt($ch, CURLOPT_USERPWD, config('api_user') . ':' . config('api_pwd'));
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
        $isDev = config('is_dev');

        // Em dev ou em caso de retorno inválido/erro HTTP, exibe mensagens detalhadas na tela
        if (!is_object($obj) || ($isDev && $httpCode >= 400)) {
            echo '<pre style="background:#f8f9fa;color:#212529;padding:15px;border:1px solid #ced4da;border-radius:4px;font-family:monospace;">', PHP_EOL;
            echo '<strong>Erro no retorno da API (HTTP ', $httpCode, '):</strong>', PHP_EOL, PHP_EOL;
            echo '<strong>Endpoint:</strong> ', API, $endpoint, PHP_EOL;
            echo '<strong>Postdata:</strong> ', json_encode($postdata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
            echo '<strong>Retorno:</strong> ', PHP_EOL;
            if (is_object($obj)) {
                echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
            } else {
                var_dump($ret);
            }
            if ($isDev || !empty($verboseLog)) {
                echo PHP_EOL, '<strong>cURL Verbose Log:</strong>', PHP_EOL;
                echo htmlspecialchars($verboseLog), PHP_EOL;
            }
            echo '</pre>', PHP_EOL;
            exit;
        }

        return $obj;
    }
}
