<?php

namespace Uspdev\Votacao\View;

use Uspdev\Votacao\Api;

class Gerente
{
    public static function sessao($id)
    {

        $sessao = Api::obterSessaoPorId($id);
        print_r($sessao);
    }
}
