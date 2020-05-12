<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;

class Admin
{
    const USER_MODEL = [
        [
            'campo' => 'codpes',
            'display' => 'Número USP',
        ]
    ];
    public function __construct()
    {
        R::selectDatabase('votacao');
    }

    public function listarUsuarios()
    {
        return R::findAll('usuario');
    }
}
