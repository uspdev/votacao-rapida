<?php

namespace Uspdev\Votacao\Model;

use \RedBeanPHP\R as R;

class Sessao
{

    public static function listar($usuario)
    {
        if ($usuario->admin == 1) {
            $sessoes = R::findAll('sessao');
        } else {
            $sessoes = $usuario->sharedSessao;
        }
        if (!$sessoes) {
            $sessoes = ['status' => 'ok', 'data' => 'Sem sessões para listar'];
        }
        return $sessoes;
    }

    public static function obterPorId($sessao_id, $usuario)
    {
        if ($usuario->codpes == '1575309') {
            $sessao = R::load('sessao', $sessao_id);
        } else {
            $sessoes = $usuario->withCondition('sessao.id = ?', [$sessao_id])->sharedSessaoList;
            if (empty($sessoes)) {
                return false;
            }
            $sessao = array_pop($sessoes);
        }
        return $sessao;
    }

    public static function obterPorHash($hash) {
        return R::findOne('sessao', 'hash = ?', [$hash]);
    }

    public static function criar($usuario, $data)
    {
        $sessao = R::dispense('sessao');
        $sessao->unidade = $usuario->unidade;
        $sessao->ano = date('Y');
        $sessao->nome = $data->nome;
        $sessao->quando = date('d/m/Y');
        $sessao->hash = generateRandomString(20);
        $sessao->estado = 'Em elaboração';
        $sessao->email = $usuario->email;
        $sessao->logo = '';
        $sessao->link = '';
        $sessao->sharedUsuarioList[] = $usuario;
        $id = R::store($sessao);

        Token::gerarTokensControle($sessao);
        //return ['status' => 'ok', 'data' => $id];
        return $sessao;
    }

    public static function remover($sessao)
    {
        // vamos limpar tokens
        R::trashAll($sessao->ownTokenList);

        $votacoes = $sessao->ownVotacaoList;
        foreach ($votacoes as $votacao) {
            // alternativas e respostas
            R::trashAll($votacao->ownAlternativaList);
            R::trashAll($votacao->ownRespostaList);
        }
        // votacoes
        R::trashAll($votacoes);

        // arquivos
        exec('rm ' . ARQ . '/' . $sessao->hash . '*.pdf', $out, $ret);

        // e finalmente a sessao
        R::trash($sessao);
        return true;
    }

    public static function atualizar($sessao, $data)
    {
        foreach ($data as $key => $val) {
            if (in_array($key, ['nome', 'unidade', 'ano', 'estado', 'logo', 'link', 'email', 'quando'])) {
                $sessao->$key = $val;
            }
        }
        return R::store($sessao);
    }
}
