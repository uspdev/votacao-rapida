<?php

namespace Uspdev\Votacao\View;

class Admin
{
    public static function home(){
        $endpoint = '/admin/listarUsuarios';
        $usuarios = Api::send($endpoint);
        //echo '<pre>';print_r($users);exit;
        $tpl = new Template('admin/home.html');
        foreach($usuarios as $u) {
            $tpl->U = $u;
            $tpl->block('block_usuarios');
        }
        $tpl->show('userbar');
    }
}
