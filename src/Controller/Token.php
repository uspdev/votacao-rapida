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

    protected static function gerarTokensVotacao($sessao, $qt)
    {
        $tipos = [
            ['tipo' => 'fechada', 'qt' => $qt],
            ['tipo' => 'aberta', 'qt' => $qt],
        ];
        return SELF::gerarTokens($sessao, $tipos);
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

    protected static function db()
    {
        R::selectDatabase('votacao');
    }
}
