<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

use \RedBeanPHP\R as R;

# VOTACAO

putenv('USPDEV_VOTACAO_LOCAL=' . realpath(__DIR__ . '/../local'));

// caminho da API
putenv('USPDEV_VOTACAO_API=http://localhost/git/uspdev/votacao/public/api');

//R::addDatabase('votacao', 'sqlite:' . getenv('USPDEV_VOTACAO_LOCAL') . '/votacao.db3');
R::addDatabase('votacao', 'mysql:host=localhost;dbname=votacao', 'votacao', 'votacao');

R::selectDatabase('votacao');
R::useFeatureSet('latest');
R::freeze(false);

// --------------------------------
$amb = 'dev';
if ($amb == 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    # 1-faz o navegador solicitar as credenciais do usuário;
    # 0-nega acesso (default)
    putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=0');
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

# O dominio vamos tentar adivinhar, se não for em linha de comando
if (PHP_SAPI != 'cli') {
    putenv('DOMINIO=http://' . $_SERVER['HTTP_HOST'] . $base);
}

# Local onde o webservice colocará arquivos sqlite, logs, etc.
# Mandatário
putenv('USPDEV_WEBSERVICE_LOCAL=' . getenv('USPDEV_VOTACAO_LOCAL'));

# Rota para gerencimaneto do webservice . default='ws'
putenv('USPDEV_WEBSERVICE_ADMIN_ROUTE=ws');

require_once __DIR__ . '/config.php';
