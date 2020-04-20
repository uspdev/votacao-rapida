<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;

class Gerente
{
    public static function sessao($id)
    {
        SELF::db();
        return R::load('sessao', $id);
    }

    public static function listarTokens($hash)
    {
        SELF::db();
        $sessao = R::findOne('sessao', 'hash = ?', [$hash]);
        return $sessao->ownTokenList;
    }

    protected static function db()
    {
        R::selectDatabase('votacao');
    }
}
