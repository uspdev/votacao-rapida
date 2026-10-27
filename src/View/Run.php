<?php

namespace Uspdev\Votacao\View;

use Uspdev\Votacao\View\SessaoPhp as SS;

class Run
{
    // tela inicial para quem entra com o link encurtado
    public static function hashGet($hash)
    {
        $_SESSION['votacao'] = [];
        $sessao = SELF::obterSessao($hash, '');

        $tpl = new Template('token.html');
        $tpl->S = $sessao;

        if (!empty($_SESSION['msg'])) {
            //print_r(json_decode($_SESSION['msg']));exit;
            $tpl->M = json_decode($_SESSION['msg']);
            $tpl->block('block_M');
            unset($_SESSION['msg']);
        }

        $tpl->show();
    }

    // post do token
    public static function hashPost($hash, $data)
    {
        // vamos verificar o token enviado e obter os dados ou retornar
        // para a tela de solicitação do token com alguma mensagem
        if (!empty($data->token)) {
            $token = $data->token;
            SELF::hashToken($hash, $token);
        } else {
            header('Location: ' . config('wwwroot') . '/' . $hash);
            exit;
        }
    }

    // tela inicial de quem entra com qrcode
    public static function hashToken($hash, $token)
    {
        $sessao = SELF::obterSessao($hash, $token);
        $perfil = $sessao->token->tipo;

        if ($perfil == 'fechada' || $perfil == 'aberta') {
            $perfil = 'votacao';
        }

        $token = [];
        $token[$sessao->token->tipo]['token'] = $sessao->token->token;

        SS::atribuir('hash', $hash);
        SS::atribuir($perfil, $token);
        // print_r($_SESSION);
        // exit;
        header('Location: ' . config('wwwroot') . '/' . $perfil);
        exit;
    }

    public static function votacaoGet()
    {
        // vamos ver se o token já está na session
        list($hash, $token) = SS::verificaSessao('votacao');
        if (empty($token)) {
            header('Location:' . $hash);
            exit;
        }

        // vamos tentar votar com os tokens existentes
        foreach ($token as $t) {
            $sessao = SELF::obterSessao($hash, $t);
            if (empty($sessao->msg) && isset($sessao->render_form)) {
                $token = $t;
                break;
            }
        }

        $tpl = new Template('votacao_index.html');
        $tpl->S = $sessao;

        // print_r($token);
        // exit;

        // se veio msg é porque houve algum problema
        if (!empty($sessao->msg)) {
            $tpl->msg = $sessao->msg;
            $tpl->block('block_msg');
            $tpl->show();
            exit;
        }

        // se não veio msg, vamos continuar
        $v = $sessao->render_form;

        if (!empty($msg = SS::getDel('votacao_msg'))) {
            // aqui trata o retorno do post
            $msg = json_decode($msg);

            if ($msg->status == 'ok') {
                $msg->msg = 'Voto computado com sucesso';
                $msg->datajson = json_encode($msg->data);
                $msg->voto = $msg->data;
                $block = 'block_msg_sucesso';
            } else {
                $msg->datajson = '';
                $block = 'block_msg_erro';
            }
            $tpl->M = $msg;

            $tpl->V = $v;
            $tpl->block($block);
        } else {
            // aqui mostra o form de votacao

            $tpl->V = $v;
            $form = new Form($v);
            $tpl->form = $form->render();
            $tpl->block('block_form');
        }

        $v->tipo = $v->tipo == 'aberta' ? 'Voto aberto' : 'Voto fechado';
        if (!empty($token->apelido)) {
            $v->tipo_class = 'hide';
        } else {
            $v->tipo_class = '';
        }
        $v->estado = 'Em votação';
        $v->estado_class = SELF::getEstadoClass($v->estado);

        $tpl->show();
    }

    public static function votacaoPost($data)
    {
        list($hash, $token) = SS::verificaSessao('votacao');
        foreach ($token as $t) {
            $sessao = SELF::obterSessao($hash, $t);
            if (empty($sessao->msg) && isset($sessao->render_form)) break;
        }
        //$sessao = SELF::obterSessao($hash, $token);

        // post de formulario
        if (isset($data->acao)) {
            $data = $_POST;
            $res = Api::post($hash, $sessao->token->token, $data);

            SS::set('votacao_msg', json_encode($res));
            //$_SESSION['msg'] = json_encode($res); //exit;
            header('Location:' .  config('wwwroot') . '/votacao');
            exit;
        }
    }


    public static function apoioGet()
    {
        list($hash, $token) = SS::verificaSessao('apoio');

        $sessao = SELF::obterSessao($hash, $token);

        // acoes para a rota de apoio
        if (isset($_GET['acao'])) {
            $data = ['acao' => $_GET['acao'], 'votacao_id' => $_GET['votacao_id']];
            $res = Api::post($hash, $token, $data);
            // temos de devolver res de alguma forma se houver erro
            //print_r($res);exit;
            header('Location: ' . config('wwwroot') . '/apoio');
            exit;
        }

        //print_r($sessao);exit;
        $tpl = new Template('apoio_index.html');

        $tpl->S = $sessao;
        $tpl->hash_estado = self::gerarHashApoio($sessao);

        // Verifica se há alguma votação em andamento ou em exibição de resultado (Em votação, Em pausa ou Resultado)
        $votacaoEmAndamento = null;
        if (!empty($sessao->votacoes)) {
            foreach ($sessao->votacoes as $item) {
                if (in_array($item->estado, ['Em votação', 'Em pausa', 'Resultado'])) {
                    $votacaoEmAndamento = $item;
                    break;
                }
            }
        }

        if ($votacaoEmAndamento) {
            $tpl->andamento_nome = $votacaoEmAndamento->nome;
            $tpl->andamento_estado = $votacaoEmAndamento->estado;
            $tpl->block('block_aviso_andamento');

            $tpl->instantaneo_disabled = 'disabled';
            $tpl->instantaneo_attr = 'tabindex="-1" aria-disabled="true" style="pointer-events:none;opacity:0.5;" title="Desabilitado: há uma votação ativa ou em exibição de resultado"';
        } else {
            $tpl->instantaneo_disabled = '';
            $tpl->instantaneo_attr = '';
        }

        foreach ($sessao->votacoes as $v) {
            $v->tipo = $v->tipo == 'aberta' ? 'Voto aberto' : 'Voto fechado';
            $v->estadoclass = SELF::getEstadoClass($v->estado);
            $v->accordion = new \stdClass();
            if ($v->estado == 'Fechada' or $v->estado == 'Finalizado') {
                $v->accordion->mostrar = '';
                $v->accordion->disabled = ($votacaoEmAndamento && $v->id != $votacaoEmAndamento->id) ? 'disabled' : '';
                $v->accordion->border = '';
            } else {
                $v->accordion->mostrar = 'show';
                $v->accordion->disabled = ($votacaoEmAndamento && $v->id != $votacaoEmAndamento->id) ? 'disabled' : '';
                $v->accordion->border = ($votacaoEmAndamento && $v->id == $votacaoEmAndamento->id) ? 'border-primary mb-3' : 'mb-3';
            }
            $tpl->V = $v;

            foreach ($v->acoes as $acao) {
                $tpl->cod = $acao->cod;
                $tpl->acao = $acao->nome;

                // Se houver votação ativa/em andamento/resultado, desabilita os botões de todas as outras votações
                if ($votacaoEmAndamento && $v->id != $votacaoEmAndamento->id) {
                    $tpl->acao_class = 'btn-secondary disabled';
                    $tpl->acao_href = 'javascript:void(0)';
                    $tpl->acao_attr = 'tabindex="-1" aria-disabled="true" style="pointer-events:none;opacity:0.5;" title="Desabilitado: a votação \'' . htmlspecialchars($votacaoEmAndamento->nome, ENT_QUOTES) . '\' está ativa/em exibição"';
                } else {
                    $tpl->acao_class = 'btn-primary';
                    $tpl->acao_href = 'apoio?acao=' . $acao->cod . '&votacao_id=' . $v->id;
                    $tpl->acao_attr = '';
                }

                $tpl->block('block_acao');
            }
            $tpl->block('block_votacao');
        }
        $tpl->show();
    }
    public static function apoioPost($dataObj)
    {
        list($hash, $token) = SS::verificaSessao('apoio');
        //$sessao = SELF::obterSessao($hash, $token);
        switch ($dataObj->acao) {
            case 'instantaneo':
                $data['acao'] = '9';
                $data['texto'] = $dataObj->texto;
                //print_r($data);

                $res = Api::post($hash, $token, $data);
                //var_dump($res);exit;
                break;
        }

        header('Location: ' . config('wwwroot') . '/apoio');
        exit;
    }

    /**
     * Gera um hash MD5 representativo do estado das votações para o painel de apoio.
     *
     * @param object $sessao Dados da sessão obtidos da API
     * @return string Hash MD5
     */
    protected static function gerarHashApoio($sessao)
    {
        $statusList = [];
        if (!empty($sessao->votacoes)) {
            foreach ($sessao->votacoes as $v) {
                $statusList[] = [
                    'id' => $v->id,
                    'estado' => $v->estado,
                ];
            }
        }
        return md5(json_encode($statusList));
    }

    /**
     * Retorna o status das votações em JSON para sincronização em tempo real entre dispositivos de apoio.
     *
     * @return void
     */
    public static function apoioStatus()
    {
        if (!isset($_SESSION['apoio']) || !isset($_SESSION['hash'])) {
            \Flight::json(['status' => 'erro', 'msg' => 'Sem sessão de apoio ativa'], 401);
            return;
        }

        $hash = $_SESSION['hash'];
        $token = $_SESSION['apoio']['apoio']['token'] ?? '';
        if (empty($token)) {
            \Flight::json(['status' => 'erro', 'msg' => 'Token de apoio não encontrado'], 401);
            return;
        }

        $sessao = SELF::obterSessao($hash, $token);

        \Flight::json([
            'status' => 'ok',
            'hash' => self::gerarHashApoio($sessao),
            'total' => !empty($sessao->votacoes) ? count($sessao->votacoes) : 0,
        ]);
    }

    /**
     * Gera um hash MD5 representativo do estado atual do painel de exibição.
     *
     * @param object $sessao Dados da sessão obtidos da API
     * @return string Hash MD5
     */
    protected static function gerarHashPainel($sessao)
    {
        $painelData = [
            'msg' => $sessao->msg ?? '',
            'em_tela' => !empty($sessao->em_tela),
            'nome' => $sessao->em_tela->nome ?? '',
            'estado' => $sessao->em_tela->estado ?? '',
            'computados' => $sessao->em_tela->computados ?? 0,
        ];
        if (!empty($sessao->em_tela->respostas)) {
            $painelData['respostas'] = $sessao->em_tela->respostas;
        }
        if (!empty($sessao->em_tela->votos)) {
            $painelData['votos_count'] = count($sessao->em_tela->votos);
        }

        return md5(json_encode($painelData));
    }

    /**
     * Retorna o status atual do painel em JSON para sincronização em tempo real sem recarregamento contínuo.
     *
     * @return void
     */
    public static function painelStatus()
    {
        if (!isset($_SESSION['painel']) || !isset($_SESSION['hash'])) {
            \Flight::json(['status' => 'erro', 'msg' => 'Sem sessão de painel ativa'], 401);
            return;
        }

        $hash = $_SESSION['hash'];
        $token = $_SESSION['painel']['painel']['token'] ?? '';
        if (empty($token)) {
            \Flight::json(['status' => 'erro', 'msg' => 'Token de painel não encontrado'], 401);
            return;
        }

        $sessao = SELF::obterSessao($hash, $token);

        \Flight::json([
            'status' => 'ok',
            'hash' => self::gerarHashPainel($sessao),
            'em_tela' => !empty($sessao->em_tela),
            'computados' => $sessao->em_tela->computados ?? 0,
            'estado' => $sessao->em_tela->estado ?? '',
        ]);
    }

    public static function painelGet()
    {
        list($hash, $token) = SS::verificaSessao('painel');

        $sessao = SELF::obterSessao($hash, $token);

        $tpl = new Template('painel_index.html');
        $tpl->block('block_topo_img');

        $tpl->S = $sessao;
        $tpl->hash_estado = self::gerarHashPainel($sessao);

        // se não houver votação
        if (!empty($sessao->msg)) {
            $tpl->msg = $sessao->msg;
            $tpl->block('block_msg');
            $tpl->show();
            exit;
        }

        $v = $sessao->em_tela;
        $v->tipo = $v->tipo == 'aberta' ? 'Voto aberto' : 'Voto fechado';

        // vamos formatar a apresentação do estado
        $v->estado_class = SElF::getEstadoClass($v->estado);

        $tpl->V = $v;

        if ($v->estado == 'Resultado') {

            if (!empty($v->respostas)) {
                foreach ($v->respostas as $r) {
                    $tpl->R = $r;
                    $tpl->block('resultado_resposta');
                }
            }

            //vamos mostrar o total de votos computados
            $tpl->block('block_computados');

            if (!empty($v->votos) && $v->tipo == 'Voto aberto') {
                $i = 0;
                if (count($v->votos) > 10) {
                    $dividir = intdiv(count($v->votos), 3);
                } else {
                    $dividir = count($v->votos) - 1;
                }
                // ordenando por apelido
                // usort($v->votos, function ($a, $b) {
                //     return strcmp(strtoupper($a->apelido), strtoupper($b->apelido));
                // });
                foreach ($v->votos as $voto) {
                    $tpl->voto = $voto;
                    $tpl->block('resultado_voto');
                    if ($dividir == $i or $dividir * 2 == $i) {
                        $tpl->block('block_coluna');
                    }
                    $i++;
                }
                $tpl->block('block_coluna');
            }

            $tpl->block('block_resultado');
        } elseif ($v->estado == 'Em exibição' || $v->estado == 'Em votação' || $v->estado == 'Em pausa') {

            if ($v->estado == 'Em votação') {
                $tpl->block('block_em_votacao');
            }
            $tpl->block('block_computados');

            if (!empty($v->descricao)) {
                $tpl->block('exibicao_descricao');
            }

            if (!empty($v->alternativas)) {
                foreach ($v->alternativas as $a) {
                    $tpl->alternativa = $a->texto;
                    $tpl->block('exibicao_alternativa');
                }
            }
            $tpl->block('block_exibicao');
        }

        $tpl->show();
    }

    public static function recepcao()
    {
        list($hash, $token) = SS::verificaSessao('recepcao');

        $sessao = SELF::obterSessao($hash, $token);

        //print_r($sessao);exit;
        $tpl = new Template('recepcao_index.html');
        $tpl->S = $sessao;
        $tpl->show();
    }

    public static function hashTicket($hash, $ticket)
    {
        SS::set('hash', $hash);
        // se o novo ticket for diferente do existente (ou se não houver existente)
        if ($ticket != SS::get('ticket')) {
            SS::set('ticket', $ticket);
            SS::unset('token_fechado');
        }
        header('Location: ' . config('wwwroot') . '/ticket');
        exit;
    }

    public function ticket()
    {
        $hash = SS::get('hash');
        $ticket = SS::get('ticket');

        // se limpou a session vamos mostrar uma mensagem
        if (empty($hash) || empty($ticket)) {
            $tpl = new Template('ticket_erro.html');
            $tpl->show();
            exit;
        }

        $sessao = Api::obterSessao($hash, $ticket);
        // a sessao está correta mas o ticket já foi utilizado
        if (empty($sessao->token) && empty(SS::get('token_fechado'))) {
            $tpl = new Template('ticket_erro.html');
            $tpl->S = $sessao;
            $tpl->block('block_sessao');
            $tpl->show();
            exit;
        }

        $endpoint = '/run/' . $hash . '/' . $ticket;

        // acoes post
        if ($this->method == 'POST') {
            $token = API::send($endpoint, $this->data->getData());
            if (!empty($token)) {
                SS::set('token_fechado', $token);
            }
            header('Location:' . $_SERVER['REDIRECT_URL']);
            exit;
        }

        // acoes get
        if (!empty($this->query->acao)) {
            switch ($this->query->acao) {
                case 'obterPdf':
                    $data['acao'] = $this->query->acao;
                    $token = SS::get('token_fechado');
                    $data['token'] = $token->token;
                    $ret = API::send($endpoint, $data);
                    //print_r($token_base64);exit;
                    $token_pdf = base64_decode($ret->pdf);
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: inline; filename="token_fechado.pdf"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . strlen($token_pdf));
                    echo $token_pdf;
                    exit;
                    break;

                case 'finalizar':
                    SS::destroy();
                    header('Location:' . $_SERVER['REDIRECT_URL']);
                    exit;
                    break;
            }
        }

        // tudo certo, vamos motrar as telas
        $tpl = new Template('ticket_index.html');
        $tpl->S = $sessao;
        if ($token = SS::get('token_fechado')) {
            // se já pegou o token vamos mostrar
            $tpl->T = json_decode(json_encode($token));
            $tpl->block('block_token');
        } else {
            // se nao pegou o token vamos pedir a confirmação
            $tpl->block('block_confirm');
        }
        $tpl->show();
    }
    
    protected static function getEstadoClass($estado)
    {
        switch ($estado) {
            case 'Em exibição':
                return 'badge-primary';
                break;
            case 'Em votação':
                return 'badge-success';
                break;
            case 'Em pausa':
                return 'badge-warning';
                break;
            case 'Resultado':
                return 'badge-primary';
                break;
            case 'Finalizado':
                return 'badge-danger';
                break;
            case 'Fechada':
                return 'badge-success';
                break;
        }
    }

    protected static function obterSessao($hash, $token)
    {
        $sessao = Api::obterSessao($hash, $token);

        // se a sessão não existir e vir erro fatal
        if (isset($sessao->status) && $sessao->status == 'erro') {
            $tpl = new Template('erro.html');
            $tpl->msg = $sessao->msg;
            $tpl->show();
            exit;
        }
        return $sessao;
    }

    // protected static function template($addFile)
    // {
    //     $tpl = new Template(TPL . '/main_template.html');
    //     $tpl->wwwroot = getenv('WWWROOT');

    //     $tpl->addFile('corpo', TPL . '/' . $addFile);
    //     return $tpl;
    // }

    // protected static function verificaSessao($tipo)
    // {
    //     if (isset($_SESSION[$tipo]['hash'])) {
    //         $hash = $_SESSION[$tipo]['hash'];
    //         $token = $_SESSION[$tipo]['token'];
    //         return [$hash, $token];
    //     } else {
    //         // vamos voltar ao inicio
    //         $tpl = new Template('erro_sem_sessao.html');
    //         $tpl->show();
    //         exit;
    //     }
    // }
}
