<?php
require_once __DIR__ . '/../vendor/autoload.php';

$amb = 'dev';
if ($amb == 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    # 1-faz o navegador solicitar as credenciais do usuário;
    # 0-nega acesso (default)
    putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=1');
}

if ($amb == 'prod') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

# veja https://github.com/uspdev/cache
putenv('USPDEV_CACHE_DISABLE=1');

# veja https://github.com/uspdev/ip-control
putenv('USPDEV_IP_CONTROL=localhost');

// vamos remover barra no final que não precisa, aparece no php -S
$base = Flight::request()->base;
$base = (substr($base, -1) == '/') ? substr($base, 0, -1) : $base;

# O dominio vamos tentar adivinhar.
putenv('DOMINIO=http://' . $_SERVER['HTTP_HOST'] . $base);

# Local onde o webservice colocará arquivos sqlite, logs, etc.
# Mandatário
putenv('USPDEV_WEBSERVICE_LOCAL=' . realpath(__DIR__ . '/../local'));

# Rota para gerencimaneto do webservice . default='ws'
putenv('USPDEV_WEBSERVICE_ADMIN_ROUTE=ws');

require_once __DIR__ . '/config.php';
