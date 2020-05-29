<?php

namespace Uspdev\Votacao\Model;

use \RedBeanPHP\R as R;

class Votacao
{
    // obtem as alternativas de uma votação
    public static function listarAlternativa($votacao)
    {
        $res = [];
        // foreach ($votacao->ownAlternativaList as $alternativa) {
        //     $alt['texto'] = $alternativa->texto;
        //     $q = 'SELECT count(id) as total
        //         FROM resposta
        //         WHERE alternativa_id = ? AND votacao_id = ? AND last = 1';
        //     $alt['votos'] = R::getCell($q, [$alternativa->id, $votacao->id]);
        //     $res[] = $alt;
        // }
        foreach ($votacao->ownAlternativaList as $a) {
            $a2 = clone $a;
            $a2->num_resposta = $a->withCondition('last = 1')->countOwn('resposta');
            $res[] = $a2;
        };
        return $res;
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

    public static function exportar($votacao)
    {
        $sessao = clone $votacao->sessao;
        $export = clone $votacao;
        $export->ownAlternativa = SELF::listarAlternativa($votacao);
        $export->num_resposta_valida = $votacao->withCondition('last = 1')->countOwn('resposta');
        $export->num_resposta = $votacao->countOwn('resposta');
        $export->ownResposta = SELF::listarResposta($votacao, true);
        $export->ownEleitorFechado = $sessao->withCondition('ticket = ? ORDER BY apelido ASC', [''])->ownTokenList;
        $arq = ARQ . '/' . $sessao->hash . '-' . 'votacao_' . $votacao->id . '-resultado.json';
        file_put_contents($arq, json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        Email::sendExportarVotacao($export);
        return $export;
    }

    // dada uma votação obtem as respostas (votos) válidas registradas,
    // ou seja, ignora os votos repetidos
    public static function listarResposta($votacao, $todos = false)
    {
        if ($todos) {
            $sql_todos = '';
        } else {
            $sql_todos = ' AND r.last = 1 ';
        }
        return R::convertToBeans(
            'resposta',
            R::getAll(
                'SELECT r.*, a.texto as alternativa
                FROM resposta as r, alternativa as a
                WHERE r.alternativa_id = a.id AND r.votacao_id = ? ' . $sql_todos . '
                ORDER BY r.apelido ASC, r.token ASC, r.last ASC',
                [$votacao->id]
            )
        );
    }

    public static function contarResposta($votacao)
    {
        return R::getCell(
            'SELECT count(id) FROM resposta WHERE votacao_id = ? and last = 1',
            [$votacao->id]
        );
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

    public static function adicionar($sessao, $data)
    {
        $votacao = R::dispense('votacao');
        $votacao->sessao = $sessao;
        $votacao->estado = 0;
        $votacao->input_type = 'checkbox';
        $votacao->input_count = '1';
        foreach ($data as $key => $val) {
            if (in_array($key, ['nome', 'descricao', 'tipo'])) {
                $votacao->$key = trim($val);
            }
        }
        R::store($votacao);

        if (!empty($data->alternativas)) {
            SELF::adicionarAlternativas($votacao, $data->alternativas);
        }
        return true;
    }

    public static function atualizar($votacao, $data)
    {
        // a verificação por data_ini é preferencial mas como foi implementado depois de 22/5/2020
        // vamos verificar as respostas também
        if (empty($votacao->data_ini) and empty($votacao->ownRespostaList)) {
            // se não tiver sido votado, vamos editar
            foreach ($data as $key => $val) {
                // vamos aceitar do $data somente os campos autorizados
                if (in_array($key, ['nome', 'descricao', 'tipo'])) {
                    $votacao->$key = trim($val);
                }
            }
            R::store($votacao);

            // vamos mexer nas algternativas somente se for enviado
            if (!empty($data->alternativas)) {
                SELF::adicionarAlternativas($votacao, $data->alternativas);
            }
            return true;
        } else {
            // se ja tiver sido votado não faremos nada
            return false;
        }
    }

    public static function adicionarAlternativas($votacao, $alternativas_string)
    {
        R::trashAll($votacao->ownAlternativaList); //limpando as existentes primeiro
        $votacao->ownAlternativaList = [];
        $alternativas = explode(PHP_EOL, $alternativas_string);
        foreach ($alternativas as $texto) {
            if (!empty(trim($texto))) {
                $a = R::dispense('alternativa');
                $a->votacao = $votacao;
                $a->texto = trim($texto);
                R::store($a);
            }
        }
        return true;
    }

    public static function remover($id)
    {
        $votacao = R::load('votacao', $id);
        // a verificação por data_ini é preferencial mas como foi implementado depois de 22/5/2020
        // vamos verificar as respostas também
        if (empty($votacao->data_ini) and !empty($votacao->ownRespostaList)) {
            // se nao foi votado removemos as alternativas 
            R::trashALl($votacao->ownAlternativaList);
            // R::trashAll($votacao->ownRespostaList);
            R::trash($votacao);
            return true;
        } else {
            // se já foi votado não fazemos nada
            return false;
        }
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
        $resposta->apelido = $sessao->token->apelido;
        $resposta->datetime = date("Y-m-d H:i:s");
        $resposta->dispositivo = substr($data->user_agent, 0, 190);
        $resposta->signature = sha1(json_encode($resposta) . $sessao->hash);
        $resposta->last = 1;
        R::store($resposta);
        $resposta->alternativa;

        return $resposta;
    }
}
