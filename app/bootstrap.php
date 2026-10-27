<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

use \RedBeanPHP\R as R;
use Uspdev\Votacao\Model\Migrationlogger;

# Constantes do Sistema
define('ROOTDIR', realpath(__DIR__ . '/..'));
define('LOCAL', ROOTDIR . '/local'); // Arquivos gerados pelo sistema
define('ARQ', LOCAL . '/arquivos'); // Arquivos das votações
define('TPL', ROOTDIR . '/template');

// Carrega as configurações da aplicação (lê o .env e define valores padrão)
$config = require ROOTDIR . '/config/app.php';

define('API', $config['api_url']); // caminho da API

// Desativa avisos de depreciação globalmente para manter compatibilidade com RedBean e bibliotecas legadas no PHP 8.2+
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

$amb = $config['ambiente'];
if ($amb == 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    // Ativa exibição de erros do Flight no navegador
    \Flight::set('flight.debug', true);

    # 1-faz o navegador solicitar as credenciais do usuário;
    # 0-nega acesso (default)
    putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=0');
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    \Flight::set('flight.debug', false);
}

// Sempre registrar erros do Flight no log
\Flight::set('flight.log_errors', true);

// Desativa cache memcached por padrão (mantendo comportamento existente)
putenv('USPDEV_CACHE_DISABLE=1');

// Compatibilidade com Flight v2 / envio imediato antes de exit
\Flight::set('flight.v2.output_buffering', true);
\Flight::response()->v2_output_buffering = true;

# Local onde o webservice colocará arquivos sqlite, logs, etc.
putenv('USPDEV_WEBSERVICE_LOCAL=' . LOCAL);

# Rota para gerenciamento do webservice. default='ws'
putenv('USPDEV_WEBSERVICE_ADMIN_ROUTE=ws');

// Conexão com DB
if ($config['db_tipo'] == 'sqlite') {
    R::addDatabase('votacao', 'sqlite:' . LOCAL . '/votacao.db3');
} else {
    R::addDatabase('votacao', $config['db_dsn'], $config['db_username'], $config['db_password']);
}

R::selectDatabase('votacao');
R::useFeatureSet('latest');
if ($amb == 'dev') {
    R::freeze(false);
    // vamos monitorar mudanças no BD
    $ml = new Migrationlogger(sprintf(ROOTDIR . '/sql/migration_%s.sql', date('Y-m-d')));
    R::getDatabaseAdapter()->getDatabase()->setLogger($ml)->setEnableLogging(TRUE);
}
if ($amb == 'prod') {
    // em produção a estrutura do BD está congelada
    R::freeze(true);
}
