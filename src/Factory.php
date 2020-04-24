<?php

namespace Uspdev\Votacao;

use Uspdev\Votacao\View\Gerente;

class Factory
{
    public static function gerente($inject)
    {
        $obj = new Gerente();
        foreach ($inject as $key => $val) {
            $obj->$key = $val;
        }
        return $obj;
    }
}
