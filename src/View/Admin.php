<?php

namespace Uspdev\Votacao\View;

class Admin
{
    public static function home(){
        $tpl = new Template('admin/home.html');
        $tpl->show('userbar');
    }
}
