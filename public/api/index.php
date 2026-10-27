<?php

require_once __DIR__.'/../../app/bootstrap.php';
# ------------------------------------------------------------------
# COISAS QUE GERALMENTE FICAM NO INDEX.PHP -------------------------

use Uspdev\Ipcontrol\Ipcontrol;
use Uspdev\Webservice\Webservice as WS;

// Controle de acesso por IP (uspdev/ip-control)
// Por padrão, restringe o acesso aos endereços de loopback (localhost)
if (!getenv('USPDEV_IP_CONTROL')) {
    putenv('USPDEV_IP_CONTROL=localhost');
}
if (!Ipcontrol::proteger(false)) {
    http_response_code(403);
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'erro',
        'msg' => 'Acesso negado: API de uso restrito ao servidor local.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tratamento padronizado de erros para a API (sempre retornar JSON estruturado)
\Flight::map('error', function (\Throwable $e) {
    if (\Flight::get('flight.log_errors')) {
        error_log('[API Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    }

    $isDev = getenv('AMBIENTE') === 'dev';
    $erro = [
        'status' => 'erro',
        'msg' => $e->getMessage(),
        'code' => $e->getCode(),
    ];

    // Se estiver em ambiente de desenvolvimento, exibe detalhes completos na resposta
    if ($isDev) {
        $erro['exception'] = get_class($e);
        $erro['file'] = $e->getFile();
        $erro['line'] = $e->getLine();
        $erro['trace'] = explode("\n", $e->getTraceAsString());
    }

    \Flight::response()
        ->clearBody()
        ->status(500)
        ->header('Content-Type', 'application/json; charset=utf-8')
        ->write(json_encode($erro, JSON_UNESCAPED_UNICODE | ($isDev ? JSON_PRETTY_PRINT : 0)))
        ->send();
    exit;
});

// Polyfill para HTTP Basic Auth no PHP-FPM / FastCGI
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (empty($auth) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (stripos($auth, 'basic ') === 0) {
        $exploded = explode(':', base64_decode(substr($auth, 6)), 2);
        if (count($exploded) == 2) {
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = $exploded;
        }
    }
}

# Controlador de gerencia do webservice (opcional).
# Se comentar essa linha não haverá uma interface de gerenciamento web.
# Para alterar o caminho veja USPDEV_WEBSERVICE_ADMIN_ROUTE
WS::admin();

# Aqui chamamos como http://servidor/controlador/metodo/parametro
$classes['gerente'] = 'Uspdev\Votacao\Controller\Gerente';
$classes['admin'] = 'Uspdev\Votacao\Controller\Admin';
WS::classes($classes);

$metodos['run'] = 'Uspdev\Votacao\Controller\Run::run';
//$metodos['sessao'] = 'Uspdev\Votacao\Controller\Gerente::sessao';
WS::metodos($metodos);

# Para listar os controladores disponíveis
WS::raiz(array_merge($classes,$metodos));

# Vamos carregar tudo o que é necessário.
WS::iniciar();
