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

    // exportar gera um arquivo no filesystem e envia um relatório por email
    public static function exportar($votacao)
    {
        $sessao = clone $votacao->sessao;
        $export = clone $votacao;
        $export->ownAlternativa = SELF::listarAlternativa($votacao);
        $export->num_resposta_valida = $votacao->withCondition('last = 1')->countOwn('resposta');
        $export->num_resposta = $votacao->countOwn('resposta');
        $export->ownResposta = SELF::listarResposta($votacao->id, true);
        $export->eleitor_fechado = $sessao->withCondition('ticket = ? ORDER BY apelido ASC', [''])->ownTokenList;
        $arq = ARQ . '/' . $sessao->hash . '-' . 'votacao_' . $votacao->id . '-resultado.json';
        file_put_contents($arq, json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        Email::sendExportarVotacao($export);
        return $export;
    }

    // dada uma votação obtem as respostas (votos) válidas registradas,
    // ou seja, ignora os votos repetidos
    public static function listarResposta($votacao_id, $todos = false)
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
                [$votacao_id]
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

    public static function novoInstantaneo($sessao, $texto)
    {
        $data['nome'] = $texto;
        $data['descricao'] = '';
        $data['tipo'] = 'aberta';
        $data['alternativas'] = 'Favorável' . PHP_EOL . 'Contrário' . PHP_EOL . 'Abstenção';

        return SELF::adicionar($sessao, (object) $data);
    }

    public static function obter($votacao_id)
    {
        return R::load('votacao', $votacao_id);
    }

    public static function adicionar($sessao, $data)
    {
        $ordem = (empty($data->ordem)) ? 0 : $data->ordem;
        if ($ordem < 0) {
            Log::votacao(
                'erro adicionar votacao',
                [
                    'sessao_id' => $sessao->id,
                    'usr_msg' => 'Ordem inválida para adicionar votação',
                    'data' => $data
                ]
            );
            return ['status' => 'erro', 'data' => 'Ordem inválida para adicionar votação'];
        }

        R::begin();
        try {
            $votacao = R::dispense('votacao');
            $votacao->sessao_id = $sessao->id;
            $votacao->ordem = SELF::atualizarOrdem($votacao, $ordem);
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
            R::commit();
            return ['status' => 'ok', 'data' => 'Votação adicionada com sucesso.'];
        } catch (\Exception $e) {
            R::rollback();
            Log::db(
                'erro adicionar votacao',
                [
                    'sessao_id' => $sessao->id,
                    'err_msg' => $e->getMessage(),
                    'usr_msg' => 'A votação não pode ser adicionada. Se o erro persistir entre em contato com o suporte.',
                    'data' => $data->getData()
                ]
            );
            return [
                'status' => 'erro',
                'data' => 'A votação não pode ser adicionada. Se o erro persistir entre em contato com o suporte.'
            ];
        }
    }

    public static function editar($votacao, $data)
    {
        // a verificação por data_ini é preferencial mas como foi implementado depois de 22/5/2020
        // vamos verificar as respostas também
        if (empty($votacao->data_ini) and empty($votacao->ownRespostaList)) {
            // se não tiver sido votado, vamos editar
            R::begin();
            try {
                if (!empty($data->ordem)) {
                    $votacao->ordem = SELF::atualizarOrdem($votacao, $data->ordem);
                }
                foreach ($data as $key => $val) {
                    // vamos aceitar do $data somente os campos autorizados
                    if (in_array($key, ['nome', 'descricao', 'tipo'])) {
                        $votacao->$key = trim($val);
                    }
                }
                R::store($votacao);

                // vamos mexer nas alternativas somente se for enviado
                if (!empty($data->alternativas)) {
                    SELF::adicionarAlternativas($votacao, $data->alternativas);
                }

                R::commit();
                return ['status' => 'ok', 'data' => 'Votação atualizada com sucesso.'];
            } catch (\Exception $e) {
                R::rollback();
                $usr_msg = 'A votação não pode ser editada. Se o erro persistir entre em contato com o suporte.';
                Log::db(
                    'erro editar votacao',
                    [
                        'sessao_id' => $votacao->sessao_id,
                        'err_msg' => $e->getMessage(),
                        'usr_msg' => $usr_msg,
                        'data' => $data->getData()
                    ]
                );
                return ['status' => 'erro', 'data' => $usr_msg];
            }
        } elseif ($data->obs) {
            // se já foi votado e veio observação, vamos aceitar
            $votacao->obs = $data->obs;
            R::store($votacao);
            return ['status' => 'ok', 'data' => 'Editado observação pós votação'];

        } else {
            // se ja tiver sido votado não faremos nada, não precisamos gerar log
            return ['status' => 'erro', 'data' => 'Impossível editar uma votação que já foi votada'];
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
        if (empty($votacao->data_ini) and empty($votacao->ownRespostaList)) {
            R::begin();
            try {
                // se nao foi votado removemos as alternativas 
                R::trashALl($votacao->ownAlternativaList);
                // R::trashAll($votacao->ownRespostaList);
                $votacao->ordem = SELF::atualizarOrdem($votacao, -1);
                R::trash($votacao);
                R::commit();
                return ['status' => 'ok', 'data' => 'Votação removida com sucesso.'];
            } catch (\Exception $e) {
                R::rollback();
                $usr_msg = 'A votação não pode ser removida. Se o erro persistir entre em contato com o suporte.';
                Log::db(
                    'erro remover votacao',
                    [
                        'sessao_id' => $votacao->sessao_id,
                        'err_msg' => $e->getMessage(),
                        'usr_msg' => $usr_msg,
                        'votacao_id' => $id,
                    ]
                );
                return ['status' => 'erro', 'data' => $usr_msg];
            }
        } else {
            // se já foi votado não fazemos nada
            return ['status' => 'erro', 'data' => 'Impossível remover uma votação que já foi votada'];
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

    // se $votacao não possuir ordem, é novo registro
    // se $ordem_nova for < 0, vai apagar registro
    // se $ordem_nova for == 0, vai para o final
    // se $ordem_nova for > 0, vai para a posição indicada
    protected static function atualizarOrdem($votacao, $ordem_nova = 0)
    {
        $max = R::getCell(
            'SELECT MAX(ordem) FROM votacao WHERE sessao_id = ?',
            [$votacao->sessao_id]
        );

        if (empty($max)) {
            $max = 0;
            $novo = true;
        }

        if (empty($votacao->ordem)) {
            $novo = true;
            $ordem_atual = $max + 1;
        } else {
            $novo = false;
            $ordem_atual = $votacao->ordem;
        }

        // verifica se vai para o final
        if ($ordem_nova == 0 || $ordem_nova > $max) {
            $ordem_nova = ($novo) ? $max + 1 : $max;
        }

        // se for negativo é delete
        if ($ordem_nova < 0) {
            R::exec(
                'UPDATE votacao SET ordem = ordem-1 WHERE ordem > ? AND sessao_id = ?',
                [$ordem_atual, $votacao->sessao_id]
            );
            return $ordem_atual;
        }

        // se for igual não faz nada
        if ($ordem_nova == $ordem_atual) {
            return $ordem_nova;
        }

        // atualizar para um valor: descendo
        if ($ordem_nova > $ordem_atual) {
            R::exec(
                'UPDATE votacao SET ordem = ordem-1 WHERE ordem > ? AND ordem <= ? AND sessao_id = ?',
                [$ordem_atual, $ordem_nova, $votacao->sessao_id]
            );
            return $ordem_nova;
        }

        // atualizar para um valor: subindo
        if ($ordem_nova < $ordem_atual) {
            R::exec(
                'UPDATE votacao SET ordem = ordem+1 WHERE ordem < ? AND ordem >= ? AND sessao_id = ?',
                [$ordem_atual, $ordem_nova, $votacao->sessao_id]
            );
            return $ordem_nova;
        }
    }
}
