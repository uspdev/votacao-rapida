<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;

class Run
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
            case ['recepcao', 'GET']:
                return SELF::recepcao($sessao);
                break;
        }
        return false;
    }

    protected static function votacaoGet($sessao)
    {
        // primeiro vamos ver se tem alguma votação com estado 'Em votação'
        //list($sessao->msg, $sessao->render_form);
        $ret = Votacao::obterEmVotacao($sessao);
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
        $ret = Votacao::obterEmVotacao($sessao);
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
                    $resposta = Votacao::computarVoto($sessao, $votacao, $data);
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
            $sql = 'SELECT cod, nome FROM acao WHERE cod IN (';
            foreach (explode(',', $estado->acoes) as $l) {
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
                Votacao::limparVotosExistentes($votacao);

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
                Votacao::limparVotosExistentes($votacao);

                $votacao->estado = $acao->estado;
                R::store($votacao);
                return ['msg' => $acao->msg];
                break;

            case '7': // finalizar
                $votacao->estado = $acao->estado;
                $votacao->data_fim = date("Y-m-d H:i:s");
                R::store($votacao);

                // vamos exportar para um arquivo externo
                Votacao::exportar($sessao, $votacao);

                return ['msg' => $acao->msg];
                break;

            case '9': // criar instantaneo
                // aqui não precisa de $votacao, pois vai criar uma nova
                $votacao = Votacao::novoInstantaneo($data['texto']);
                $votacao->sessao_id = $sessao->id;
                R::store($votacao);

                return ['status' => 'ok', 'msg' => 'Votação adicionada com sucesso'];
                break;

            case '10':
                R::trash($votacao);
                return ['status' => 'ok', 'msg' => 'Votação excluída com sucesso'];
                break;
        }
    }

    // todo:-------------
    protected static function recepcao($sessao)
    {
        unset($sessao->link);
        return $sessao;
    }

    protected static function painel($sessao)
    {
        // vamos ver se tem alguma votação para ser exibida na tela
        $painel = '';
        foreach ($sessao->ownVotacaoList as $votacao) {

            switch ($votacao->estado) {
                case '1': // em tela
                case '2': // em votacao
                case '3': // em pausa
                    // pegar as alternativas
                    $votacao->alternativas = $votacao->ownAlternativaList;
                    $painel = $votacao;
                    break;
                case '4': // resultado
                    // mostra o resultado
                    $votacao->respostas = Votacao::listarRespostas($votacao);

                    //vamos obter os votos
                    //$votos = R::findAll('resposta', 'votacao_id = ? and last = 1', [$votacao->id]);
                    $votos = R::getAll(
                        'SELECT r.*, a.texto as alternativa
                         FROM resposta as r, alternativa as a
                         WHERE r.alternativa_id = a.id AND r.votacao_id = ? AND r.last = 1
                         ORDER BY r.nome ASC',
                        [$votacao->id]
                    );
                    $votacao->votos = $votos;

                    $painel = $votacao;
                    break;
            }
            if ($painel) break;
        }

        if (!$painel) {
            $sessao->msg = 'Sem votação aberta';
            SELF::limparSaida($sessao);
            return SELF::limparSaida($sessao);
        }

        // vamos obter o total de votos computados
        // precisa para 2,3 e 4
        $painel->computados = R::getCell(
            'SELECT count(id) FROM resposta WHERE votacao_id = ? and last = 1',
            [$painel->id]
        );

        // vamos colocar o nome do estado
        $painel->estado = R::getCell('SELECT nome FROM estado WHERE cod = ' . $painel->estado);

        $sessao->em_tela = $painel;
        return SELF::limparSaida($sessao);
    }



    // protected static function obterVotacaoNaoFechadaFinalizada($sessao)
    // {
    //     foreach ($sessao->ownVotacaoList as $votacao) {
    //         if ($votacao->estado != 0 && $votacao->estado != 5) {
    //             $votacao->alternativas = $votacao->ownAlternativaList;
    //             return $votacao;
    //         }
    //     }
    // }

    protected static function limparSaida($sessao)
    {
        unset($sessao->id);
        //unset($sessao->hash);
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
}
