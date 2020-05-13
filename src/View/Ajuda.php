<?php

namespace Uspdev\Votacao\View;

use \RedBeanPHP\R as R;

class Ajuda {
    public static function inicio() {
        $tpl = new Template('ajuda.html');
        //$tpl->msg = $msg;
        $tpl->show('userbar');
       // exit;
    }
}