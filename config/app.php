<?php

/**
 * Configuração Geral da Aplicação — Votação Rápida
 *
 * Este arquivo carrega o arquivo .env, define os valores padrão
 * e sincroniza as variáveis de ambiente.
 *
 * O restante da aplicação lê este arquivo para obter as configurações:
 *   $config = require ROOTDIR . '/config/app.php';
 *   // ou através da função auxiliar: config('chave', 'padrao')
 */

$rootDir = realpath(__DIR__ . '/..');

// 1. Carrega o Composer Autoload se chamado isoladamente
if (!class_exists('Dotenv\Dotenv')) {
    $autoload = $rootDir . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

// 2. Carrega o arquivo .env se existir no diretório raiz
if (file_exists($rootDir . '/.env') && class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir . '/');
    $dotenv->safeLoad();
}

// 3. Helper local para ler do ambiente (getenv, $_ENV ou $_SERVER) com fallback
$env = function (string $key, $default = null) {
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    return $default;
};

// 4. Monta as configurações lendo do .env e definindo os valores padrão
$wwwroot = rtrim($env('WWWROOT', 'http://localhost'), '/');
$apiUrl = $env('API_URL') ?: ($wwwroot . '/api');

$config = [
    // Ambiente
    'ambiente' => $env('AMBIENTE', 'prod'),
    'is_dev' => ($env('AMBIENTE', 'prod') === 'dev'),

    // URLs
    'wwwroot' => $wwwroot,
    'api_url' => $apiUrl,
    'api_resolve_ip' => $env('API_RESOLVE_IP', '127.0.0.1'),
    'api_verify_ssl' => ($env('API_VERIFY_SSL', 'true') !== 'false'),

    // API Interna
    'api_user' => $env('API_USER', 'admin'),
    'api_pwd' => $env('API_PWD', 'admin'),
    'uspdev_ip_control' => $env('USPDEV_IP_CONTROL', 'localhost'),

    // Banco de Dados
    'db_tipo' => $env('DB_TIPO', 'mysql'),
    'db_dsn' => $env('DB_DSN', 'mysql:host=localhost;dbname=votacao'),
    'db_username' => $env('DB_USERNAME', 'admin'),
    'db_password' => $env('DB_PASSWORD', ''),

    // Senha Única USP
    'senhaunica_key' => $env('CONSUMER_KEY') ?: $env('SENHAUNICA_KEY', ''),
    'senhaunica_secret' => $env('CONSUMER_SECRET') ?: $env('SENHAUNICA_SECRET', ''),
    'senhaunica_callback_id' => $env('SENHA_UNICA_CALLBACK_ID') ?: $env('SENHAUNICA_CALLBACK_ID', ''),
    'senhaunica_amb' => $env('SENHA_UNICA_AMB') ?: $env('SENHAUNICA_AMB', 'prod'),
    // Admin Inicial
    'admin_codpes' => $env('ADMIN_CODPES', ''),
    
    // E-mails
    'email_auth_type' => strtolower($env('EMAIL_AUTH_TYPE', 'log')),
    'email_host' => $env('EMAIL_HOST', 'smtp.gmail.com'),
    'email_port' => (int) $env('EMAIL_PORT', 465),
    'email' => $env('EMAIL', ''),
    'email_pwd' => $env('EMAIL_PWD', ''),
    'email_oauth_client_id' => $env('EMAIL_OAUTH_CLIENT_ID', ''),
    'email_oauth_client_secret' => $env('EMAIL_OAUTH_CLIENT_SECRET', ''),
    'email_oauth_refresh_token' => $env('EMAIL_OAUTH_REFRESH_TOKEN', ''),

    // CLI
    'php_bin' => $env('PHP_BIN') ?: $env('PHP_CLI', null),
];

// 5. Mapeia chaves para sincronização no ambiente (.env / putenv)
$envSync = [
    'AMBIENTE' => $config['ambiente'],
    'WWWROOT' => $config['wwwroot'],
    'API_URL' => $config['api_url'],
    'API_RESOLVE_IP' => $config['api_resolve_ip'],
    'API_VERIFY_SSL' => $config['api_verify_ssl'] ? 'true' : 'false',
    'API_USER' => $config['api_user'],
    'API_PWD' => $config['api_pwd'],
    'USPDEV_IP_CONTROL' => $config['uspdev_ip_control'],
    'DB_TIPO' => $config['db_tipo'],
    'DB_DSN' => $config['db_dsn'],
    'DB_USERNAME' => $config['db_username'],
    'DB_PASSWORD' => $config['db_password'],
    'CONSUMER_KEY' => $config['senhaunica_key'],
    'CONSUMER_SECRET' => $config['senhaunica_secret'],
    'SENHA_UNICA_CALLBACK_ID' => $config['senhaunica_callback_id'],
    'SENHA_UNICA_AMB' => $config['senhaunica_amb'],
    'EMAIL_AUTH_TYPE' => $config['email_auth_type'],
    'EMAIL_HOST' => $config['email_host'],
    'EMAIL_PORT' => (string) $config['email_port'],
    'EMAIL' => $config['email'],
    'EMAIL_PWD' => $config['email_pwd'],
    'EMAIL_OAUTH_CLIENT_ID' => $config['email_oauth_client_id'],
    'EMAIL_OAUTH_CLIENT_SECRET' => $config['email_oauth_client_secret'],
    'EMAIL_OAUTH_REFRESH_TOKEN' => $config['email_oauth_refresh_token'],
    'ADMIN_CODPES' => $config['admin_codpes'],
    'PHP_BIN' => $config['php_bin'] ?? '',
];

// Sincroniza putenv(), $_ENV e disponibiliza também as chaves em maiúsculas no $config
foreach ($envSync as $envKey => $envVal) {
    if (getenv($envKey) === false || getenv($envKey) === '') {
        putenv("{$envKey}={$envVal}");
        $_ENV[$envKey] = (string) $envVal;
        $_SERVER[$envKey] = (string) $envVal;
    }
    $config[$envKey] = $envVal;
}

// Guarda na variável global para acesso rápido pela função config()
$GLOBALS['__app_config'] = $config;

// 6. Define a função auxiliar global config() para recuperação rápida
if (!function_exists('config')) {
    /**
     * Recupera um valor de configuração da aplicação.
     *
     * @param string|null $key Chave de configuração (ex: 'wwwroot', 'db_dsn', 'AMBIENTE')
     * @param mixed $default Valor retornado caso a chave não exista
     * @return mixed Array completo se $key for null, ou o valor da configuração
     */
    function config(?string $key = null, $default = null)
    {
        $cfg = $GLOBALS['__app_config'] ?? null;
        if ($cfg === null) {
            $cfg = require __DIR__ . '/app.php';
        }

        if ($key === null) {
            return $cfg;
        }

        return $cfg[$key] ?? $cfg[strtolower($key)] ?? $cfg[strtoupper($key)] ?? $default;
    }
}

return $config;
