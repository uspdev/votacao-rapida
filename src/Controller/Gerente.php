<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;

class Gerente
{
    public function sessao($id)
    {
        //$query = \Flight::request()->query;
        if (empty($this->query->codpes)) return 'sem codpes';

        SELF::db();
        $sessao = R::load('sessao', $id);
        if ($this->method == 'POST') {
            switch ($this->data->acao) {
                case 'addUser':
                    $usuario = R::findOrCreate('usuario', [
                        'codpes' => $this->data->codpes,
                    ]);
                    $sessao->sharedUsuarioList[] = $usuario;
                    R::store($sessao);
                    return ['status' => 'ok', 'data' => json_encode($usuario->export())];
                    break;
                case 'delUser':
                    $usuario = $sessao->withCondition('usuario.id = ? ', [$this->data->id])->sharedUsuarioList;
                    unset($sessao->sharedUsuarioList[key($usuario)]);
                    R::store($sessao);
                    return $usuario;
                    break;
            }
            return ['status' => 'erro', 'data' => 'Sem ação para ' . $this->data->acao];
        }
        $sessao->sharedUsuarioList;
        return $sessao;
    }

    public function listarSessoes()
    {
        if (empty($this->query->codpes)) return 'sem codpes';

        SELF::db();
        $sessoes = R::findAll('sessao');
        return $sessoes;
    }

    public function listarTokens($hash)
    {
        SELF::db();
        $sessao = R::findOne('sessao', 'hash = ?', [$hash]);
        return $sessao->ownTokenList;
    }

    public function login()
    {
        $userdata = $this->data;
        SELF::db();
        if (!$user = R::findOne('usuario', 'codpes = ?', [$userdata['codpes']])) {
            $user = R::dispense('usuario');
        };
        foreach ($userdata as $key => $val) {
            $user->$key = $val;
        }
        $user->lastlogin = date('"Y-m-d H:i:s"');
        R::store($user);
        return $user;
    }

    protected static function db()
    {
        R::selectDatabase('votacao');
    }
}
