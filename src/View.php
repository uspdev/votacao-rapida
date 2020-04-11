<?php

namespace Uspdev\Votacao;

use raelgc\view\Template;

class View
{
    // index
    public static function index() {
        $tpl = SELF::template('index.html');
        $tpl->show();
        exit;
    }

    // tela inicial para quem entra com o link encurtado
    public static function hashGet($hash)
    {
        $sessao = SELF::obterSessao($hash, '');

        $tpl = SELF::template('token.html');
        $tpl->S = $sessao;

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

        //print_r($sessao);exit;
        $tpl = SELF::template('votacao_index.html');

        $tpl->S = $sessao;
        if (!empty($sessao->msg)) {
            $tpl->msg = $sessao->msg;
            $tpl->block('block_msg');
        } else {
            $form = new Form($sessao->render_form);
            $tpl->form = $form->render();
            $tpl->block('block_form');
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
            $res = Curl::post($hash, $sessao->token->token, $data);
            echo json_encode($res); //exit;
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
            $res = post($hash, $token, $data);
            // temos de devolver res de alguma forma se houver erro

            header('Location: ' . getenv('WWWROOT') . '/apoio');
            exit;
        }

        $tpl = SELF::template('apoio_index.html');

        $tpl->S = $sessao;
        foreach ($sessao->votacoes as $v) {
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

    public static function painelGet()
    {
        list($hash, $token) = SELF::verificaSessao('painel');

        $sessao = SELF::obterSessao($hash, $token);

        $tpl = SELF::template('tela_index.html');

        $tpl->S = $sessao;
        if (!empty($sessao->msg)) {
            $tpl->msg = $sessao->msg;
            $tpl->block('block_msg');
        } else {
            $tpl->V = $sessao->em_tela;
            if (!empty($sessao->em_tela->alternativas)) {
                foreach ($sessao->em_tela->alternativas as $a) {
                    $tpl->alternativa = $a->texto;
                    $tpl->block('block_alternativa');
                }
            }
            if (!empty($sessao->em_tela->respostas)) {
                foreach ($sessao->em_tela->respostas as $r) {
                    $tpl->R = $r;
                    $tpl->block('block_resposta');
                }
            }
            $e = $sessao->em_tela->estado;
            if ($e == 'Em votação' or $e == 'Em pausa' or $e == 'Resultado') {
                $tpl->block('block_computados');
            }

            $tpl->block('block_votacao');
        }

        $tpl->show();
    }

    protected static function obterSessao($hash, $token)
    {
        $sessao = Curl::get($hash, $token);
        if (!empty($sessao->status) && $sessao->status == 'erro') {
            echo '<pre>';
            echo 'Erro ao obter sessão: ', PHP_EOL;
            echo json_encode($sessao);
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
            $tpl = SELF::template('erro_sem_sessao.html');
            $tpl->show();
            exit;
        }
    }
}
