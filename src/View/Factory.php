<?php

namespace Uspdev\Votacao\View;

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

    public static function aviso($inject)
    {
        $obj = new Aviso();
        foreach ($inject as $key => $val) {
            $obj->$key = $val;
        }
        return $obj;
    }
    
    public static function run($inject)
    {
        $obj = new Run();
        foreach ($inject as $key => $val) {
            $obj->$key = $val;
        }
        return $obj;
    }
}
