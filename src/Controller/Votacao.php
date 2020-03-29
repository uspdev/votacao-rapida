<?php namespace Uspdev\Votacao\Controller;

use Uspdev\Votacao\Model\Sessao as Sessao;

class Votacao
{
    public static function run($hash, $token)
    {
        $query = \Flight::request()->query;
        $files = \Flight::request()->files;

        $sessao = new Sessao($hash);
        $sessao->token_tipo = $sessao->validaToken($token);
        if (empty($sessao->token_tipo)) {
            return 'token inválido';
        }
        
        if ($sessao->estado == 'aberto') {
            return $sessao;
        }

        if ($sessao->estado == 'fechado') {
            return $sessao;
        }
        return 'votacao, hash=' . $hash . ', token=' . $token;
    }

    public static function gerente($hash, $token)
    {
        return 'gerente';
    }
    public static function recepcao($hash, $token)
    {
        return 'recepcao';
    }
    public static function tela($hash, $token)
    {
        return 'tela';
    }
    public static function login($codpes)
    {
        return 'registr o login de ' . $codpes;
    }
    public static function admin()
    {
        return 'admin';
    }

    public static function sessao($id = null, $token = null)
    {
        $method = \Flight::request()->method;
        if ($method == 'GET') {
            return SELF::getSessao($id, $token);
        }
        if ($method == 'POST') {
            return 'acoes de post de sessao';
        }

    }

    protected static function getSessao($id, $token)
    {
        $query = \Flight::request()->query;
        if (empty($query->codpes)) {
            return 'Codpes não informado';
        }

        if (!empty($token)) {
            if ($token == 'token') {
                return SELF::retornaTokens($id);
            } else {
                return 'erro de parametros';
            }
        }

        if (empty($id)) {
            return 'mostra lista de sessões autorizadas para codpes=' . $query->codpes;
        }

        return 'dados da sessao de votacao id=' . $id;
    }

    protected static function retornaTokens($sessao_id)
    {
        $tipos_de_token = ['apoio', 'tela', 'recepcao', 'votacao'];

        $query = \Flight::request()->query;
        $tipo = empty($query->tipo) ? 'votacao' : $query->tipo;
        if (!in_array($tipo, $tipos_de_token)) {
            return 'tipo inexistente';
        }
        return 'retorna lista de tokens do tipo ' . $tipo . ' para sessao_id=' . $sessao_id;
    }

}
