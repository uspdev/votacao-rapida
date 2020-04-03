<?php

require_once __DIR__.'/../../app/bootstrap.php';
# ------------------------------------------------------------------
# COISAS QUE GERALMENTE FICAM NO INDEX.PHP -------------------------

use Uspdev\Ipcontrol\Ipcontrol;
use Uspdev\Webservice\Webservice as WS;

// vamos limitar o acesso por IP
Ipcontrol::proteger();

# Controlador de gerencia do webservice (opcional).
# Se comentar essa linha não haverá uma interface de gerenciamento web.
# Para alterar o caminho veja USPDEV_WEBSERVICE_ADMIN_ROUTE
WS::admin();

# Aqui chamamos como http://servidor/controlador/metodo/parametro
$classes['votacao'] = 'Uspdev\Votacao\Controller\Votacao';
WS::classes($classes);

$metodos['run'] = 'Uspdev\Votacao\Controller\Votacao::run';
WS::metodos($metodos);

# Para listar os controladores disponíveis
WS::raiz(array_merge($classes,$metodos));

# Vamos carregar tudo o que é necessário.
WS::iniciar();
