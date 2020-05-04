<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;

class Token
{
    public static function gerarTokensControle($sessao)
    {
        $tipos = [
            ['tipo' => 'apoio', 'qt' => 1],
            ['tipo' => 'painel', 'qt' => 1],
            ['tipo' => 'recepcao', 'qt' => 1],
        ];
        return SELF::gerarTokens($sessao, $tipos);
    }

    public static function gerarTokensVotacao($sessao, $qt)
    {
        $tipos = [
            ['tipo' => 'fechada', 'qt' => $qt],
            ['tipo' => 'aberta', 'qt' => $qt],
        ];
        return SELF::gerarTokens($sessao, $tipos);
    }

    public static function adicionarTokenAberto($sessao, $eleitor)
    {
        $e = array_map('trim', $eleitor);
        //$eleitor = array_intersect_key($eleitor, array_flip(['apelido', 'nome', 'email']));

        if (R::find('token', 'sessao_id = ? and (apelido = ? or nome = ? or email = ?)', [$sessao->id, $e['apelido'], $e['nome'], $e['email']])) {
            return false; // já existe 
        }

        while ($newToken = generateRandomString(6)) {
            $token = R::find('token', 'sessao_id = ? and token = ?', [$sessao->id, $newToken]);
            if (!$token) {
                $token = R::dispense('token');
                $token->tipo = 'aberta';
                $token->token = $newToken;
                $token->import($e);
                $token->ativo = 0;
                $token->sessao_id = $sessao->id;
                R::store($token);
                break;
            }
            //echo 'gerou repetido! ', $newToken,PHP_EOL;exit;
        }
        return $token;
    }

    protected static function gerarTokens($sessao, $tipos)
    {
        SELF::db();
        foreach ($tipos as $tipo) {
            while ($newToken = generateRandomString(6)) {
                $token = R::find('token', 'sessao_id = ? and token = ?', [$sessao->id, $newToken]);
                if (!$token) {
                    $token = R::dispense('token');
                    $token->tipo = $tipo['tipo'];
                    $token->token = $newToken;
                    $token->nome = '';
                    $token->apelido = '';
                    $token->email = '';
                    $token->ativo = 0;
                    $token->sessao_id = $sessao->id;
                    R::store($token);
                    break;
                }
                //echo 'gerou repetido! ', $newToken,PHP_EOL;exit;
            }
        }
        return true;
    }

    public static function listarTokens($hash)
    {
        SELF::db();
        $sessao = R::findOne('sessao', 'hash = ?', [$hash]);
        return $sessao->ownTokenList;
    }

    public static function ObterToken($sessao, $tipo)
    {
        SELF::db();
        return R::findOne('token', 'sessao_id = ? AND tipo = ?', [$sessao->id, $tipo]);
    }

    protected static function db()
    {
        R::selectDatabase('votacao');
    }
}
