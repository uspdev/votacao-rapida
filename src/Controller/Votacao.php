<?php namespace Uspdev\Votacao\Controller;

use Uspdev\Votacao\Model\Sessao as Sessao;
use \RedBeanPHP\R as R;

class Votacao
{
    public static function run($hash, $token)
    {
        $query = \Flight::request()->query;
        $files = \Flight::request()->files;
        R::selectDatabase('votacao');

        // verifica o hash e carrega os dados da sessão
        $sessao = R::findOne('sessao', 'hash = ?', [$hash]);
        if (empty($sessao)) {
            return 'hash inválido';
        }

        // verifica o token
        $sessao->token = R::findOne('token', 'sessao_id = ? and token = ?', [$sessao->id, $token]);
        if (empty($sessao->token)) {
            return 'token inválido';
        }

        switch ($sessao->token->tipo) {
            case 'votacao':
                return SELF::votacao($sessao);
                break;
            case 'apoio':
                return SELF::apoio($sessao);
                break;
            case 'tela':
                return SELF::tela($sessao);
                break;
            case 'recepcao':
                return SELF::recepcao($sessao);
                break;
        }
        return false;
    }

    protected static function obterVotacaoAberta($sessao)
    {
        foreach ($sessao->ownVotacaoList as $votacao) {
            if ($votacao->estado == 2) {
                $votacao->alternativas = $votacao->ownAlternativaList;
                return $votacao;
            }
        }
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

            // em votacao ou em pausa, mostra as alternativas
            $e = $votacao->estado;
            if ($e == 1 or $e == 2 or $e == 3) {
                $votacao->computados = R::getCell(
                    'SELECT count(id) FROM resposta WHERE votacao_id = ? and last = 1',
                    [$votacao->id]
                );
                $votacao->alternativas = $votacao->ownAlternativaList;

                $votacao->estado = R::getCell('SELECT nome FROM estado WHERE cod = ' . $votacao->estado);
                return $votacao;
            }

            // em tela, mostra o resultado
            if ($e == 4) { //em tela
                $votacao->computados = R::getCell(
                    'SELECT count(id) FROM resposta WHERE votacao_id = ? and last = 1',
                    [$votacao->id]
                );
                $votacao->respostas = SELF::listarRespostasPorVotacao($votacao);
                $votacao->estado = R::getCell('SELECT nome FROM estado WHERE cod = ' . $votacao->estado);
                return $votacao;
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
        return $r;
    }

    protected static function votacao($sessao)
    {
        if (!$sessao->em_votacao = SELF::obterVotacaoAberta($sessao)) {
            $sessao->msg = 'Sem votação aberta';
            return SELF::limparSaida($sessao);
        };

        $method = \Flight::request()->method;
        if ($method == 'POST' || $method == 'PUT') {
            $data = \Flight::request()->data;
            switch ($data->acao) {
                case 'resposta':
                    //echo $sessao->em_votacao->id;exit;
                    if ($sessao->em_votacao->id == $data->votacao_id) {
                        $resposta = $data->getData();
                        unset($resposta['acao']);
                        $resposta['token'] = $sessao->token->token;
                        $resposta['nome'] = $sessao->token->nome;
                        $resposta['datetime'] = date("Y-m-d H:i:s");
                        $resposta['dispositivo'] = \Flight::request()->user_agent;
                        $resposta['signature'] = sha1(json_encode($resposta) . $sessao->hash);
                        $resposta['last'] = 1;
                        R::exec('UPDATE resposta SET last = 0 WHERE token = ?', [$resposta['token']]);
                        R::store(R::dispense(['_type' => 'resposta'] + $resposta));
                        return $resposta;
                    }
                    return 'Voto mal formado';
                    break;
            }
            return ['msg'=>'Ação inválida: '.$data[ 'acao']];
        }

        if ($method == 'GET') {
            //$sessao->acoes = SELF::votacao_acoes;
            return SELF::limparSaida($sessao);
        }
    }

    protected static function apoio($sessao)
    {
        $method = \Flight::request()->method;

        if ($method == 'POST') {
            $data = \Flight::request()->data;

            if (!$acao = R::findOne('acao', "cod = ?", [$data['acao']])) {
                return ['msg' => 'Ação inválida: ' . $data['acao']];
            }

            $votacao = R::load('votacao', $data->votacao_id);

            switch ($data['acao']) {

                case '0':
                    $votacao->estado = $acao->estado;
                    R::store($votacao);
                    return ['msg' => 'Votação em tela'];
                    break;

                case '1':
                    $votacao->estado = $acao->estado;
                    R::store($votacao);
                    return ['msg' => 'Votação fechada'];
                    break;

                case '2': //Iniciar votação

                    // verificar se pode abrir
                    // if (!empty(SELF::obterVotacaoNaoFechadaFinalizada($sessao))) {
                    //     return ['msg' => 'Impossível iniciar. Há votações abertas ou não finalizadas'];
                    // }

                    // limpar votos existentes, se houver
                    R::exec('DELETE FROM resposta WHERE votacao_id = ' . $votacao->id);

                    $votacao->estado = $acao->estado;
                    R::store($votacao);
                    return ['msg' => $acao->msg];
                    break;

                case '3': //pausar
                    // if ($votacao->estado != 1) {
                    //     return ['msg' => 'Impossível pausar uma votação que não está aberta.'];
                    // }
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
                        getenv('USPDEV_VOTACAO_LOCAL') . '/resultado.json',
                        json_encode($save, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    );

                    return ['msg' => $acao->msg];
                    break;

            }
        }
        if ($method == 'GET') {
            $sessao->votacoes = $sessao->ownVotacaoList;
            foreach ($sessao->votacoes as &$votacao) {
                $estado = R::findOne('estado', 'cod = ?', [$votacao->estado]);
                $votacao->estado = $estado->nome;
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
    }

    protected static function recepcao($sessao)
    {
        unset($sessao->link_manual);
        return $sessao;
    }

    protected static function tela($sessao)
    {
        if (!$sessao->em_tela = SELF::obterVotacaoEmTela($sessao)) {
            $sessao->msg = 'Sem votação aberta';
            SELF::limparSaida($sessao);
        }
        // mostrar votos computados

        return SELF::limparSaida($sessao);
    }

    protected static function limparSaida($sessao)
    {
        unset($sessao->id);
        unset($sessao->hash);
        unset($sessao->estado);
        unset($sessao->tipo_votacao);
        unset($sessao->link_manual);
        unset($sessao->lista);
        unset($sessao->link_qrcode);unset($sessao->token);
        unset($sessao->ownVotacao);

        //unset($sessao->em_votacao->id);
        unset($sessao->em_votacao->estado);
        unset($sessao->em_votacao->sessao_id);
        unset($sessao->em_votacao->ownAlternativa);

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
