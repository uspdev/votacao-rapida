<?php

namespace Uspdev\Votacao;

//use Uspdev\Votacao\Curl;
use raelgc\view\Template;

class View
{

    public static function hashGet($hash)
    {
        $sessao = SELF::obterSessao($hash, '');

        $tpl = SELF::template();
        $tpl->addFile('corpo', getenv('DIR') . '/template/token.html');
        $tpl->S = $sessao;

        $tpl->show();
    }

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

        // post de formulario
        if (isset($_POST['acao'])) {
            $data = $_POST;
            $res = post($hash, $sessao->token->token, $data);
            print_r($res);
            header('Location:' .  getenv('WWWROOT') . '/votacao');
            exit;
        }

        //print_r($sessao);exit;
        $tpl = SELF::template();
        $tpl->addFile('corpo', getenv('DIR') . '/template/votacao_index.html');

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

    public static function apoioGet()
    {
        list($hash, $token) = SELF::verificaSessao('apoio');

        $sessao = SELF::obterSessao($hash, $token);

        // acoes para a rota de apoio
        if (isset($_GET['acao'])) {
            $data = ['acao' => $_GET['acao'], 'votacao_id' => $_GET['votacao_id']];
            $res = post($hash, $token, $data);
            header('Location: ' . getenv('WWWROOT') . '/apoio');
            exit;
        }

        $tpl = SELF::template();
        $tpl->addFile('corpo', getenv('DIR') . '/template/apoio_index.html');

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

        $tpl = SELF::template();
        $tpl->addFile('corpo', getenv('DIR') . '/template/tela_index.html');

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
            if (
                $sessao->em_tela->estado == 'Em votação' or
                $sessao->em_tela->estado == 'Em pausa' or
                $sessao->em_tela->estado == 'Resultado'
            ) {
                $tpl->block('block_computados');
            }

            $tpl->block('block_votacao');
        }

        $tpl->show();
    }

    public static function obterSessao($hash, $token)
    {
        $sessao = Curl::get($hash,$token);
        if (!empty($sessao->status) && $sessao->status == 'erro') {
            echo '<pre>';
            echo 'Erro ao obter sessão: ',PHP_EOL;
            echo json_encode($sessao);
            exit;
        }
        return $sessao;

        // tratamento de erro a implementar
        if (isset($sessao->status) && $sessao->status == 'erro') {
            $tpl = new Template(__DIR__ . '/../../template/erro.html');
            $tpl->msg = $sessao->msg;
            $tpl->block('block_msg');
            $tpl->show();
            exit;
        }

    }

    protected static function template()
    {
        return new Template(getenv('DIR') . '/template/main_template.html');
    }

    protected static function verificaSessao($tipo)
    {
        if (isset($_SESSION[$tipo]['hash'])) {
            $hash = $_SESSION[$tipo]['hash'];
            $token = $_SESSION[$tipo]['token'];
            return [$hash, $token];
        } else {
            // vamos voltar ao inicio
            header('Location:' . getenv('WWWROOT'));
            exit;
        }
    }
}
