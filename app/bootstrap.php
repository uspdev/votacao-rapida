<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

use \RedBeanPHP\R as R;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

# Arquivos locais
putenv('USPDEV_VOTACAO_LOCAL=' . realpath(__DIR__ . '/../local'));

// caminho da API
putenv('USPDEV_VOTACAO_API=' . getenv('WWWROOT') . '/api');

$amb = getenv('AMBIENTE');
if ($amb == 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    # 1-faz o navegador solicitar as credenciais do usuário;
    # 0-nega acesso (default)
    putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=0');

    # veja https://github.com/uspdev/cache
    putenv('USPDEV_CACHE_DISABLE=1');
}

if ($amb == 'prod') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

# veja https://github.com/uspdev/ip-control
# desativado pois dá problem quando está atrás de proxy
putenv('USPDEV_IP_CONTROL=');

// vamos remover barra no final que não precisa, aparece no php -S
$base = Flight::request()->base;
$base = (substr($base, -1) == '/') ? substr($base, 0, -1) : $base;

# O dominio vamos tentar adivinhar, se não for em linha de comando
// if (PHP_SAPI != 'cli') {
//     putenv('DOMINIO=http://' . $_SERVER['HTTP_HOST'] . $base);
// }

# Local onde o webservice colocará arquivos sqlite, logs, etc.
# Mandatário
putenv('USPDEV_WEBSERVICE_LOCAL=' . getenv('USPDEV_VOTACAO_LOCAL'));

# Rota para gerenciamento do webservice. default='ws'
putenv('USPDEV_WEBSERVICE_ADMIN_ROUTE=ws');

// Conexão com DB
if (getenv('DB_TIPO') == 'sqlite') {
    R::addDatabase('votacao', 'sqlite:' . getenv('USPDEV_VOTACAO_LOCAL') . '/votacao.db3');
} else {
    R::addDatabase('votacao', getenv('DB_DSN'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
}

R::selectDatabase('votacao');
R::useFeatureSet('latest');
R::freeze(false);
