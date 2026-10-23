<?php

require_once __DIR__.'/../../app/bootstrap.php';
# ------------------------------------------------------------------
# COISAS QUE GERALMENTE FICAM NO INDEX.PHP -------------------------

use Uspdev\Webservice\Webservice as WS;

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
