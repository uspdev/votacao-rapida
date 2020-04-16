<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;

class Votacao
{
    public static function run($hash, $token = '')
    {
        $query = \Flight::request()->query;
        $files = \Flight::request()->files;
        R::selectDatabase('votacao');

        // verifica o hash e carrega os dados da sessão
        $sessao = R::findOne('sessao', 'hash = ?', [$hash]);
        if (empty($sessao)) {
            return ['status' => 'erro', 'msg' => 'Hash inválido'];
        }

        // se nao foi enviado token é porque vai digitar manualmente.
        // vamos manda as informações básicas da sessão
        if (empty($token)) {
            return $sessao;
        }

        // verifica se o token pertence à sessão
        $sessao->token = R::findOne('token', 'sessao_id = ? and token = ?', [$sessao->id, $token]);
        if (empty($sessao->token)) {
            return ['status' => 'erro', 'msg' => 'Token inválido para essa sessão'];
        }

        $method = \Flight::request()->method;

        // tudo verificado, podemos carregar os dados de acordo com o token
        switch ([$sessao->token->tipo, $method]) {
            case ['aberta', 'GET']:
            case ['fechada', 'GET']:
                return SELF::votacaoGet($sessao);
                break;
            case ['aberta', 'POST']:
            case ['fechada', 'POST']:
                return SELF::votacaoPOST($sessao);
                break;
            case ['apoio', 'GET']:
                return SELF::apoioGet($sessao);
                break;
            case ['apoio', 'POST']:
                return SELF::apoioPost($sessao);
                break;
            case ['painel', 'GET']:
                return SELF::painel($sessao);
                break;
            case ['recepcao', '']:
                return SELF::recepcao($sessao);
                break;
        }
        return false;
    }

    protected static function votacaoGet($sessao)
    {
        // primeiro vamos ver se tem alguma votação com estado 'Em votação'
        //list($sessao->msg, $sessao->render_form);
        $ret = SELF::obterVotacaoEmVotacao($sessao);
        $sessao->msg = $ret['msg'];
        $sessao->render_form = $ret['votacao'];

        if ($sessao->render_form != null) {
            $sessao->render_form->acao = ['cod' => 8, 'nome' => 'Responder'];
        };

        return SELF::limparSaida($sessao);
    }

    protected static function votacaoPost($sessao)
    {
        // primeiro vamos ver se tem alguma votação com estado 'Em votação'
        $ret = SELF::obterVotacaoEmVotacao($sessao);
        $msg = $ret['msg'];
        $votacao = $ret['votacao'];
        $data = \Flight::request()->data;

        if ($votacao == null) {
            $ret = ['status' => 'erro', 'msg' => $msg . ', acao=' . $data->acao];
            return $ret;
        };

        switch (intval($data->acao)) {
            case '8':
                //vamos ver se o voto veio para votação correta
                if (
                    $votacao->id == $data->votacao_id &&
                    !empty($data->alternativa_id)
                ) {

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

                    return ['status' => 'ok', 'data' => $resposta];
                }

                return ['status' => 'erro', 'msg' => 'Voto mal formado para ação ' . $data['acao']];
                break;
        }

        return ['status' => 'erro', 'msg' => 'Ação inválida: ' . $data['acao']];
    }

    protected static function apoioGet($sessao)
    {
        // vamos carregar as votações
        $sessao->votacoes = $sessao->ownVotacaoList;
        foreach ($sessao->votacoes as &$votacao) {

            // vamos pegar o nome do estado
            $estado = R::findOne('estado', 'cod = ?', [$votacao->estado]);
            $votacao->estado = $estado->nome;

            // vamos obter as ações possíveis para esse estado
            $list = explode(',', $estado->acoes);

            $sql = 'SELECT cod, nome FROM acao WHERE cod IN (';
            foreach ($list as $l) {
                $sql .= intval($l) . ',';
            }
            $sql = substr($sql, 0, -1) . ')';

            $votacao->acoes = R::getAll($sql);
        }
        return SELF::limparSaida($sessao);
    }

    protected static function apoioPost($sessao)
    {
        $data = \Flight::request()->data;

        // se ação não estiver dentro das ações predefinidas, vamos abortar
        if (!$acao = R::findOne('acao', "cod = ?", [$data['acao']])) {
            return ['msg' => 'Ação inválida: ' . $data['acao']];
        }

        // vmos carregar a votação
        $votacao = R::load('votacao', $data->votacao_id);

        switch (intval($data['acao'])) {
            case '0': // mostrar na tela
                $votacao->estado = $acao->estado;
                R::store($votacao);
                return ['msg' => $acao->msg];
                break;

            case '1': // fechar
                $votacao->estado = $acao->estado;
                R::store($votacao);
                return ['msg' => $acao->msg];
                break;

            case '2': // iniciar votação
                // limpar votos existentes, se houver
                R::exec('DELETE FROM resposta WHERE votacao_id = ' . $votacao->id);

                $votacao->estado = $acao->estado;
                R::store($votacao);
                return ['msg' => $acao->msg];
                break;

            case '3': // pausar
                $votacao->estado = $acao->estado;
                R::store($votacao);
                return ['msg' => $acao->msg];
                break;

            case '4': //Mostrar resultado
                $votacao->estado = $acao->estado;
                R::store($votacao);
                return ['msg' => $acao->msg];
                break;

            case '5': // continuar
                $votacao->estado = $acao->estado;
                R::store($votacao);
                return ['msg' => $acao->msg];
                break;

            case '6': //Reiniciar
                if ($votacao->estado == 5) {
                    return ['msg' => 'Impossível reiniciar depois de encerrada'];
                }

                // limpar votos existentes, se houver
                R::exec('DELETE FROM resposta WHERE votacao_id = ' . $votacao->id);

                $votacao->estado = $acao->estado;
                R::store($votacao);
                return ['msg' => $acao->msg];
                break;

            case '7': // finalizar
                $votacao->estado = $acao->estado;
                R::store($votacao);

                SELF::exportarVotacao($sessao, $votacao);

                return ['msg' => $acao->msg];
                break;

            case '9':
                // aqui não precisa de $votacao, pois vai criar uma nova
                $votacao = SELF::novoInstantaneo($data['texto']);
                $votacao->sessao_id = $sessao->id;
                //$sessao->ownVotacaoList[] = $votacao;
                R::store($votacao);
                //echo '<pre>';
                //print_r($votacao);
                //exit;

                //return $data;
                return ['status' => 'ok', 'msg' => 'Votação adicionada com sucesso'];
                break;

            case '10':
                R::trash($votacao);
                return ['status' => 'ok', 'msg' => 'Votação excluída com sucesso'];
                break;
        }
    }

    protected static function novoInstantaneo($texto)
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

    protected static function exportarVotacao($sessao, $votacao)
    {
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
        file_put_contents(
            LOCAL . '/resultado.json',
            json_encode($save, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    protected static function recepcao($sessao)
    {
        unset($sessao->link);
        return $sessao;
    }

    protected static function painel($sessao)
    {
        if (!$sessao->em_tela = SELF::obterVotacaoEmTela($sessao)) {
            $sessao->msg = 'Sem votação aberta';
            SELF::limparSaida($sessao);
        }
        // mostrar votos computados

        //print_r($sessao->export());exit;

        return SELF::limparSaida($sessao);
    }

    protected static function obterVotacaoEmVotacao($sessao)
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
        return ['msg' => 'Sem votação aberta', 'votacao' => null];
    }

    protected static function obterVotacaoNaoFechadaFinalizada($sessao)
    {
        foreach ($sessao->ownVotacaoList as $votacao) {
            if ($votacao->estado != 0 && $votacao->estado != 5) {
                $votacao->alternativas = $votacao->ownAlternativaList;
                return $votacao;
            }
        }
    }

    protected static function obterVotacaoemTela($sessao)
    {
        foreach ($sessao->ownVotacaoList as $votacao) {

            // vamos obter o total de votos computados
            $votacao->computados = R::getCell(
                'SELECT count(id) FROM resposta WHERE votacao_id = ? and last = 1',
                [$votacao->id]
            );

            switch ($votacao->estado) {
                case '1': // em tela
                case '2': // em votacao
                case '3': // em pausa
                    // mostra as alternativas
                    $votacao->alternativas = $votacao->ownAlternativaList;

                    // vamos colocar o nome do estado
                    $votacao->estado = R::getCell('SELECT nome FROM estado WHERE cod = ' . $votacao->estado);
                    return $votacao;
                    break;
                case '4': // resultado
                    // mostra o resultado
                    $votacao->respostas = SELF::listarRespostasPorVotacao($votacao);

                    // vamos colocar o nome do estado
                    $votacao->estado = R::getCell('SELECT nome FROM estado WHERE cod = ' . $votacao->estado);
                    return $votacao;
                    break;
            }
        }
        return false;
    }

    protected static function listarRespostasPorVotacao($votacao)
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

        // $alt['texto'] = 'Brancos';
        // $q = 'SELECT count(id) as total
        //     FROM resposta
        //     WHERE alternativa_id IS NULL AND votacao_id = ? AND last = 1';
        // $alt['votos'] = R::getCell($q, [$votacao->id]);
        // $r[] = $alt;

        return $r;
    }

    protected static function limparSaida($sessao)
    {
        unset($sessao->id);
        unset($sessao->hash);
        unset($sessao->estado);
        unset($sessao->tipo_votacao);
        unset($sessao->link);
        unset($sessao->nomes_json);
        unset($sessao->link_qrcode);
        //unset($sessao->token);
        unset($sessao->ownVotacao);

        //unset($sessao->render_form->id);
        unset($sessao->render_form->estado);
        unset($sessao->render_form->sessao_id);
        unset($sessao->render_form->ownAlternativa);

        unset($sessao->em_tela->id);
        //unset($sessao->em_tela->estado);
        unset($sessao->em_tela->sessao_id);
        unset($sessao->em_tela->ownAlternativa);
        return $sessao;
    }

    public static function login($codpes)
    {
        return 'registar o login de ' . $codpes;
    }
    public static function admin()
    {
        return 'admin';
    }
}
