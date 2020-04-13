<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

use \RedBeanPHP\R as R;

# Constantes do Sistema
define('ROOTDIR', realpath(__DIR__ . '/..'));
define('LOCAL', ROOTDIR . '/local'); // Arquivos gerados pelo sistema
define('ARQ', LOCAL . '/arquivos'); // Arquivos das votações
define('TPL', ROOTDIR . '/template');

// carregando as variáveis de .env para o ambiente
$dotenv = Dotenv\Dotenv::createImmutable(ROOTDIR . '/');
$dotenv->load();

# Se o servidor estiver atrás de um proxy,
# o servidor da API deve ser colocada no /etc/hosts 
# para que não passe pelo proxy
# ex.: 12.7.0.0.1 servidor-do-wwwroot.usp.br
define('API', getenv('WWWROOT') . '/api'); // caminho da API

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
    putenv('USPDEV_CACHE_DISABLE=1');

}

# veja https://github.com/uspdev/ip-control
# desativado pois dá problema quando está atrás de proxy
putenv('USPDEV_IP_CONTROL=');

// vamos remover barra no final que não precisa, aparece no php -S
// $base = Flight::request()->base;
// $base = (substr($base, -1) == '/') ? substr($base, 0, -1) : $base;

# O dominio vamos tentar adivinhar, se não for em linha de comando
// if (PHP_SAPI != 'cli') {
//     putenv('DOMINIO=http://' . $_SERVER['HTTP_HOST'] . $base);
// }

# Local onde o webservice colocará arquivos sqlite, logs, etc.
putenv('USPDEV_WEBSERVICE_LOCAL=' . LOCAL);

# Rota para gerenciamento do webservice. default='ws'
putenv('USPDEV_WEBSERVICE_ADMIN_ROUTE=ws');

// Conexão com DB
if (getenv('DB_TIPO') == 'sqlite') {
    R::addDatabase('votacao', 'sqlite:' . LOCAL . '/votacao.db3');
} else {
    R::addDatabase('votacao', getenv('DB_DSN'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
}

R::selectDatabase('votacao');
R::useFeatureSet('latest');
R::freeze(false);
