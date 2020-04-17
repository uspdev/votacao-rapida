<?php

namespace Uspdev\Votacao;

use \RedBeanPHP\R as R;

class View
{
    // index
    public static function index()
    {
        if (!isset($_SESSION['user'])) {
            SelF::ajuda('');
        }

        if (isset($_SESSION['user'])) {
            $user = $_SESSION['user'];
            //echo '<pre>';print_r($_SESSION['user']);exit;
            //echo getenv('GOD_USER');exit;
            // buscar as sessões desse usuário
            if (!($user['loginUsuario'] == '1575309' ||
                $user['loginUsuario'] == '3567082' || //poliana
                $user['loginUsuario'] == '4807059' || //adriana
                $user['loginUsuario'] == '2508632'    //nivaldo
            )) {
                SelF::ajuda('Você não tem acesso à esse sistema.');
            }
        }

        // usuário está ok, vamos procurar dados dele
        $tpl = new Template('index.html');
        $topbar = new \stdClass();
        $user = $_SESSION['user'];
        //echo '<pre>';print_r($_SESSION['user']);exit;
        // buscar as sessões desse usuário

        $sessoes = R::findAll('sessao');
        //print_r(r::exportAll($sessoes));exit;
        foreach ($sessoes as $sessao) {
            $tpl->S = $sessao;
            $tpl->block('block_sessao');
        }

        $tpl->user = json_decode(json_encode($user)); // transformando array em obj
        $topbar->class = 'top-bar-user';
        $topbar->block = 'block_user_in';
        $tpl->block('block_user');

        $tpl->block('block_topo_img');

        $tpl->show($topbar);
        exit;
    }

    public static function login()
    {
        $auth = new \Uspdev\Senhaunica\Senhaunica([
            'consumer_key' => getenv('CONSUMER_KEY'),
            'consumer_secret' => getenv('CONSUMER_SECRET'),
            'callback_id' => getenv('SENHA_UNICA_CALLBACK_ID'), // callback_id é o sequencial no servidor
            'amb' => getenv('SENHA_UNICA_AMB'), // 'dev' = teste, 'prod' = producao
        ]);

        $res = $auth->login();
        $_SESSION['user'] = $res;
        header('Location:' . getenv('WWWROOT'));
        exit;
    }

    public static function logout()
    {
        unset($_SESSION);
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        header('Location: ' . getenv('WWWROOT'));
        exit;
    }

    public static function ajuda($msg)
    {
        $tpl = new Template('ajuda.html');
        $tpl->msg = $msg;

        $topbar = new \stdClass();
        if (isset($_SESSION['user'])) {
            $user = $_SESSION['user'];
            //echo '<pre>';print_r($_SESSION['user']);exit;
            // buscar as sessões desse usuário
            if ($user['loginUsuario'] != '1575309') {
            }

            $tpl->user = json_decode(json_encode($_SESSION['user'])); // transformando array em obj
            $topbar->class = 'top-bar-user';
            $topbar->block = 'block_user_in';
        } else {
            $topbar->class = 'top-bar-user';
            $topbar->block = 'block_user_out';
        }

        $tpl->block('block_topo_img');

        $tpl->show($topbar);
        exit;
    }

    public static function demo()
    {
        // o hash do demo é fixo
        $hash = 'UUZEWSRWKBXOGJVWIYJV';
        $acao = isset($_GET['acao']) ? $_GET['acao'] : '';

        switch ($acao) {

            case 'votar':
                //$hash = $_GET['hash'];
                require_once ROOTDIR . '/cli/funcoes_cli.php';
                gerarVotosAleatorios($hash);
                echo '<a href="demo/">Clique aqui para retornar</a>';
                exit;
                break;

            case 'reset':
                require_once ROOTDIR . '/cli/funcoes_cli.php';
                echo limparRespostas($hash), '<br>';
                echo '<A href="demo/">Clique aqui para retornar</a>';
                exit;
                break;

            case 'tokens_pdf':
                $arq = $_GET['arq'];
                header("Content-type:application/pdf");
                header("Content-Disposition:attachment;filename=tokens_qrcode.pdf");
                header('Cache-Control: public, must-revalidate, max-age=0');
                readfile(ARQ . '/' . $arq);
                exit;
                break;
        }

        R::selectDatabase('votacao');
        R::useFeatureSet('latest');

        $sessao = R::findOne('sessao', 'hash = ?', ['UUZEWSRWKBXOGJVWIYJV']);

        //print_r(R::exportAll($sessoes));

        $tpl = new Template('demo.html');
        $tpl->block('block_topo_img');

        $tokens = $sessao->ownTokenList;
        $tpl->S = $sessao;
        $counta = $countf = 1;
        foreach ($tokens as $token) {
            switch ($token->tipo) {
                case 'apoio':
                    $tpl->token_apoio = $token->token;
                    break;
                case 'painel':
                    $tpl->token_tela = $token->token;
                    break;
                case 'recepcao':
                    $tpl->token_recepcao = $token->token;
                    break;
                case 'fechada':
                    $tpl->token_votacao = $token->token;
                    $tpl->count = $countf;
                    $countf++;
                    $tpl->block('block_fechada');
                    break;
                case 'aberta':
                    $tpl->token_votacao = $token->token;
                    $tpl->count = $counta;
                    $counta++;
                    $tpl->block('block_aberta');
                    break;
            }
        }
        $tpl->block('block_sessao');

        // // vamos mostrar as relações entre estados e ações
        // // Estado => Ação
        // $estados = R::findAll('estado');
        // foreach ($estados as $e) {
        //     $acao_nome = '';

        //     // vamos expandir as acoes de cada estado
        //     foreach (explode(',', $e->acoes) as $acao_cod) {
        //         $acao = R::findOne('acao', 'cod = ?', [intval($acao_cod)]);
        //         $e_nome = R::getCell('SELECT nome FROM estado WHERE cod = ' . $acao->estado);
        //         $acao_nome .= $acao->nome . ' (-> ' . $e_nome . ') | ';
        //     }
        //     $e->acao_nome = substr($acao_nome, 0, -2);
        //     //$tpl->E = $e;
        //     //$tpl->block('block_estado');
        // }

        // //Ação: Estado inicial -> estado final
        // $acoes = R::find('acao', "escopo = 'apoio'");
        // foreach ($acoes as $a) {
        //     $a->estado = R::getCell('SELECT nome FROM estado WHERE cod = ' . $a->estado);
        //     $ini = R::getAll('SELECT nome FROM estado WHERE acoes LIKE ?', ["%$a->cod%"]);
        //     if (count($ini) == 1) {
        //         $a->estado_ini = $ini[0]['nome'];
        //     } else {
        //         $a->estado_ini = '';
        //         foreach ($ini as $i) {
        //             $a->estado_ini .= $i['nome'] . ', ';
        //         }
        //         $a->estado_ini = substr($a->estado_ini, 0, -2);
        //     }

        //     //$tpl->A = $a;
        //     //$tpl->block('block_acao');
        // }



        $tpl->show();
        exit;
    }

    // tela inicial para quem entra com o link encurtado
    public static function hashGet($hash)
    {
        $_SESSION = [];
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
            header('Location: ' . getenv('WWWROOT') . '/' . $hash);
            exit;
        }
    }

    // tela inicial de quem entra com qrcode
    public static function hashToken($hash, $token)
    {
        $sessao = SELF::obterSessao($hash, $token);
        $tipo = $sessao->token->tipo;

        if ($tipo == 'fechada' || $tipo == 'aberta') {
            $tipo = 'votacao';
        }

        $_SESSION[$tipo]['hash'] = $hash;
        $_SESSION[$tipo]['token'] = $token;
        header('Location: ' . getenv('WWWROOT') . '/' . $tipo);
        exit;
    }

    public static function votacaoGet()
    {
        list($hash, $token) = SELF::verificaSessao('votacao');
        $sessao = SELF::obterSessao($hash, $token);

        //print_r($sessao);//exit;
        $tpl = new Template('votacao_index.html');

        $tpl->S = $sessao;

        // se veio msg é porque houve algum problema
        if (!empty($sessao->msg)) {
            $tpl->msg = $sessao->msg;
            $tpl->block('block_msg');
        } else {
            $v = $sessao->render_form;

            if (!empty($_SESSION['msg'])) {
                // aqui trata o retorno do post
                $msg = json_decode($_SESSION['msg']);
                unset($_SESSION['msg']);

                if ($msg->status == 'ok') {
                    $msg->msg = 'Voto computado com sucesso';
                    $msg->datajson = json_encode($msg->data);
                    $block = 'block_msg_sucesso';
                } else {
                    $msg->datajson = '';
                    $block = 'block_msg_erro';
                }
                $tpl->M = $msg;
                $v->tipo = $v->tipo == 'aberta' ? 'Voto aberto' : 'Voto fechado';
                $tpl->V = $v;
                $tpl->block($block);
            } else {
                // aqui mostra o form de votacao
                $v->tipo = $v->tipo == 'aberta' ? 'Voto aberto' : 'Voto fechado';
                $tpl->V = $v;
                $form = new Form($v);
                $tpl->form = $form->render();
                $tpl->block('block_form');
            }
        }

        $tpl->show();
    }

    public static function votacaoPost($data)
    {
        list($hash, $token) = SELF::verificaSessao('votacao');
        $sessao = SELF::obterSessao($hash, $token);

        // post de formulario
        if (isset($data->acao)) {
            $data = $_POST;
            //print_r($data);
            //echo json_encode((Array) $data);exit;
            $res = Api::post($hash, $sessao->token->token, $data);
            $_SESSION['msg'] = json_encode($res); //exit;
            header('Location:' .  getenv('WWWROOT') . '/votacao');
            exit;
        }
    }


    public static function apoioGet()
    {
        list($hash, $token) = SELF::verificaSessao('apoio');

        $sessao = SELF::obterSessao($hash, $token);

        // acoes para a rota de apoio
        if (isset($_GET['acao'])) {
            $data = ['acao' => $_GET['acao'], 'votacao_id' => $_GET['votacao_id']];
            $res = Api::post($hash, $token, $data);
            // temos de devolver res de alguma forma se houver erro
            //print_r($res);exit;
            header('Location: ' . getenv('WWWROOT') . '/apoio');
            exit;
        }

        //print_r($sessao);exit;
        $tpl = new Template('apoio_index.html');

        $tpl->S = $sessao;
        foreach ($sessao->votacoes as $v) {
            $v->tipo = $v->tipo == 'aberta' ? 'Voto aberto' : 'Voto fechado';
            $v->estadoclass = SELF::getEstadoClass($v->estado);
            $v->accordion = new \stdClass();
            if ($v->estado == 'Fechada' or $v->estado == 'Finalizado') {
                $v->accordion->mostrar = '';
                $v->accordion->disabled = 'disabled';
                $v->accordion->border = '';
            } else {
                $v->accordion->mostrar = 'show';
                $v->accordion->disabled = '';
                $v->accordion->border = 'border-primary mb-3';
            }
            $tpl->V = $v;

            foreach ($v->acoes as $acao) {
                $tpl->cod = $acao->cod;
                $tpl->acao = $acao->nome;
                $tpl->block('block_acao');
            }
            $tpl->block('block_votacao');
        }
        $tpl->show();
    }
    public static function apoioPost($dataObj)
    {
        list($hash, $token) = SELF::verificaSessao('apoio');
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

        header('Location: ' . getenv('WWWROOT') . '/apoio');
        exit;
    }

    public static function painelGet()
    {
        list($hash, $token) = SELF::verificaSessao('painel');

        $sessao = SELF::obterSessao($hash, $token);

        $tpl = new Template('painel_index.html');
        $tpl->block('block_topo_img');

        // se não houver votação
        $tpl->S = $sessao;
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
            if (!empty($v->votos) && $v->tipo == 'Voto aberto') {
                foreach ($v->votos as $voto) {
                    $tpl->voto = $voto;
                    $tpl->block('resultado_voto');
                }
            }
            $tpl->block('resultado_computados');
            $tpl->block('block_resultado');
        } elseif ($v->estado == 'Em exibição' || $v->estado == 'Em votação' || $v->estado == 'Em pausa') {

            if ($v->estado == 'Em votação') {
                $tpl->block('block_computados');
            }

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

    protected static function getEstadoClass($estado)
    {
        switch ($estado) {
            case 'Em exibição':
                return 'badge-secondary';
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
        if (!empty($sessao->status) && $sessao->status == 'erro') {
            $_SESSION['msg'] = json_encode($sessao);
            header('Location: ' . getenv('WWWROOT') . '/' . $hash);
            exit;
        }
        return $sessao;

        // tratamento de erro a implementar
        if (isset($sessao->status) && $sessao->status == 'erro') {
            $tpl = new Template(ROOTDIR . '/template/erro.html');
            $tpl->msg = $sessao->msg;
            $tpl->block('block_msg');
            $tpl->show();
            exit;
        }
    }

    protected static function template($addFile)
    {
        $tpl = new Template(ROOTDIR . '/template/main_template.html');
        $tpl->wwwroot = getenv('WWWROOT');

        $tpl->addFile('corpo', TPL . '/' . $addFile);
        return $tpl;
    }

    protected static function verificaSessao($tipo)
    {
        if (isset($_SESSION[$tipo]['hash'])) {
            $hash = $_SESSION[$tipo]['hash'];
            $token = $_SESSION[$tipo]['token'];
            return [$hash, $token];
        } else {
            // vamos voltar ao inicio
            $tpl = new Template('erro_sem_sessao.html');
            $tpl->show();
            exit;
        }
    }
}
