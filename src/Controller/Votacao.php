<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;

class Votacao
{
    public static function listarRespostas($votacao)
    {
        $r = [];
        foreach ($votacao->ownAlternativaList as $alternativa) {
            $alt['texto'] = $alternativa->texto;
            $q = 'SELECT count(id) as total
                FROM resposta
                WHERE alternativa_id = ? AND votacao_id = ? AND last = 1';
            $alt['votos'] = R::getCell($q, [$alternativa->id, $votacao->id]);
            $r[] = $alt;
        }
        return $r;
    }

    public static function obterEmVotacao($sessao)
    {
        $em_votacao = 2; // cod da tabela estado

        foreach ($sessao->ownVotacaoList as $votacao) {
            if ($votacao->estado == $em_votacao) {
                if ($sessao->token->tipo != $votacao->tipo) {
                    return ['msg' => 'Token inválido para esta votação', 'votacao' => null];
                }

                $votacao->alternativas = $votacao->ownAlternativaList;
                return ['msg' => '', 'votacao' => $votacao];
            }
        }
        return ['msg' => 'Aguarde a próxima votação', 'votacao' => null];
    }

    public static function exportar($sessao, $votacao)
    {
        $arq = ARQ . '/' . $sessao->hash . '-' . 'votacao_' . $votacao->id . '-resultado.json';
        $save = $sessao->export();
        $v = $votacao->export();
        $v['alternativas'] = [];

        // não queremos exportar descendentes, por isso o foreach
        foreach ($votacao->ownAlternativaList as $alternativa) {
            $v['alternativas'][] = $alternativa->export();
        }
        $v['respostas'] = R::exportAll($votacao->ownRespostaList);
        $v['total_respostas'] = count($v['respostas']);
        $save['votacao'] = $v;

        $save['tokens'] = R::exportAll($sessao->ownTokenList);
        file_put_contents($arq, json_encode($save, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function novoInstantaneo($texto)
    {
        $data = [
            '_type' => 'votacao',
            'estado' => 0, // sempre inicia fechado
            'nome' => $texto,
            'descricao' => '',
            'tipo' => 'aberta',
            'input_type' => 'checkbox',
            'input_count' => '1',
            'data_ini' => '',
            'data_fim' => '',
            'ownAlternativaList' => [
                [
                    '_type' => 'alternativa',
                    'texto' => 'Favorável',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Contrário',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Abstenção',
                ],
            ]
        ];

        $votacao = R::dispense($data);
        $id = R::store($votacao);
        return R::load('votacao', $id);
    }

    public static function limparVotosExistentes($votacao)
    {
        R::exec('DELETE FROM resposta WHERE votacao_id = ' . $votacao->id);
        return true;
    }

    public static function computarVoto($sessao, $votacao, $data)
    {
        //vamos invalidar votos anteriores se houver
        R::exec(
            'UPDATE resposta SET last = 0 WHERE votacao_id = ? AND token = ?',
            [$votacao->id, $sessao->token->token]
        );

        //vamos guardar o novo voto
        $resposta = R::dispense('resposta');
        $resposta->votacao_id = $data->votacao_id;
        $resposta->alternativa_id = $data->alternativa_id;
        $resposta->token = $sessao->token->token;
        $resposta->nome = $sessao->token->nome;
        $resposta->datetime = date("Y-m-d H:i:s");
        $resposta->dispositivo = \Flight::request()->user_agent;
        $resposta->signature = sha1(json_encode($resposta) . $sessao->hash);
        $resposta->last = 1;
        R::store($resposta);
        $resposta->alternativa;

        return $resposta;
    }
}
