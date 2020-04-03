<?php namespace Uspdev\Votacao\Model;

use Uspdev\Votacao\Database as D;
use \RedBeanPHP\R as R;

class Sessao
{
    public function __construct($hash, $token)
    {
        //$sessao = D::list('sessao', ['hash' => $hash]);
        R::selectDatabase('votacao');
        $sessao = R::findOne('sessao', 'hash = ?', [$hash]);
        if (empty($sessao)) {
            return false;
        }
        foreach ($sessao as $k => $v) {
            $this->$k = $v;
        }
        $this->token = R::findOne('token', 'sessao_id = ? and token = ?', [$this->id, $token]);

    }

    public function validaToken($token)
    {
        R::selectDatabase('votacao');
        $token = R::findOne('token', 'sessao_id = ? and token = ?', [$this->id, $token]);
        return $token;

        switch ($token) {
            case 'tokenv':
                return 'votacao';
                break;
            case 'tokena':
                return 'apoio';
                break;
            case 'tokent':
                return 'tela';
                break;
            case 'tokenr':
                return 'recepcao';
                break;
            default:
                return false;
        }
    }

    public function listarVotacoes()
    {
        $votacoes = [
            ['id' => 1, 'nome' => 'Votação 1', 'estado' => '0'],
            ['id' => 2, 'nome' => 'Votação 2', 'estado' => '0'],
        ];
        return $votacoes;
    }
}
