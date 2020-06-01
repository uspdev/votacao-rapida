<?php

namespace Uspdev\Votacao\Model;

use \RedBeanPHP\R as R;

class Usuario
{
    public static function obter($codpes)
    {
        $usuario = R::findOne('usuario', 'codpes = ?', [$codpes]);
        if (empty($usuario)) {
            echo json_encode(['status' => 'erro', 'msg' => 'Usuário inválido']);
            exit;
        }
        return $usuario;
    }
}