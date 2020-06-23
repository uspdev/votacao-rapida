<?php

namespace Uspdev\Votacao\Model;

use \RedBeanPHP\R as R;

class Usuario
{
    /**
     * Obtém dados do usuário
     *
     * @param int $codpes
     * @return bean usuario | array mensagem de erro
     */
    public static function obter($codpes)
    {
        $usuario = R::findOne('usuario', 'codpes = ?', [$codpes]);
        if (empty($usuario)) {
            echo json_encode(['status' => 'erro', 'msg' => 'Usuário inválido']);
            exit;
        }
        return $usuario;
    }

    public static function obterOuCriar($codpes)
    {
        return R::findOrCreate('usuario', ['codpes' => $codpes]);
    }
}
