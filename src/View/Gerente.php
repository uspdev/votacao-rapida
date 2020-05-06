<?php

namespace Uspdev\Votacao\View;

use Uspdev\Votacao\Api;
use Uspdev\Votacao\Template;
use Uspdev\Votacao\SessaoPHP as SS;
use Uspdev\Senhaunica\Senhaunica;



class Gerente
{
    // index
    public static function index()
    {
        if (empty($user = SS::get('user'))) {
            // se nao estiver logado
            SELF::ajuda('');
        }

        $endpoint = '/gerente/listarSessoes?codpes=' . $user['codpes'];
        $sessoes = Api::send($endpoint);

        $tpl = new Template('gerente/index.html');

        // listar as sessões desse usuário se houver
        if (empty($sessoes->status)) {
            foreach ($sessoes as $sessao) {
                $tpl->S = $sessao;
                $tpl->block('block_sessao');
            }
        }
        $tpl->block('block_user');

        $tpl->show('userbar');
        exit;
    }

    public function login($cod = '')
    {
        if ($cod) {
            $user['codpes'] = $cod;
            $usr = Api::send('/gerente/login', $user);
            SS::set('user', json_decode(json_encode($usr), true));
            header('Location:' . SS::getNext());
            exit;
        }
        $auth = new Senhaunica([
            'consumer_key' => getenv('CONSUMER_KEY'),
            'consumer_secret' => getenv('CONSUMER_SECRET'),
            'callback_id' => getenv('SENHA_UNICA_CALLBACK_ID'), // callback_id é o sequencial no servidor
            'amb' => getenv('SENHA_UNICA_AMB'), // 'dev' = teste, 'prod' = producao
        ]);
        $res = $auth->login();

        // todo: ainda tem de ajustar os vinculos permitidos
        $vinculo = $auth->obterVinculo('tipoVinculo', ['SERVIDOR', 'Estagiário']);
        if ($vinculo) {
            $user['codpes'] = $res['loginUsuario'];
            $user['nome'] = $res['nomeUsuario'];
            $user['email'] = $res['emailPrincipalUsuario'];
            $user['unidade'] = $vinculo['siglaUnidade'];

            $usr = Api::send('/gerente/login', $user);
            //print_r($usr);exit;
            SS::set('user', json_decode(json_encode($usr), true));
        }

        header('Location:' . SS::getNext());
        exit;
    }

    public static function logout()
    {
        SS::destroy();

        // vamos chamar a API para registrar o logout ????
        header('Location: ' . getenv('WWWROOT'));
        exit;
    }

    public static function ajuda($msg)
    {
        $tpl = new Template('ajuda.html');
        $tpl->msg = $msg;

        $tpl->show('userbar');
        exit;
    }

    public function sessao($id, $aba)
    {
        $user = SS::getUser();
        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user['codpes'];
        $sessao = Api::send($endpoint);
        // se id = 0 cria nova sessao. Nesse caso vamos recarregar
        // para usar o id correto devolvido pela API
        if ($id == 0) {
            header('Location:' . $sessao->id);
            exit;
        }

        if ($this->method == "POST") {
            $this->sessaoPostActions($sessao);
        }

        if (!empty($this->query->acao)) {
            $this->sessaoGetActions($sessao);
        }

        if ($aba == '') {
            header('Location: ' . $_SERVER['REDIRECT_URL'] . '/votacoes');
            exit;
        }

        $tpl = new Template('gerente/sessao.html');
        $tpl->S = $sessao;

        if (!empty($sessao->status) && $sessao->status == 'erro') {
            Template::erro($sessao->msg);
        }

        // autorizacao
        $tpl->addFile('autorizacao', TPL . '/gerente/sessao_autorizacao.html');
        foreach ($sessao->sharedUsuario as $u) {
            $u->self = ($u->codpes == $user['codpes']) ? 'self' : '';
            $tpl->U = $u;
            $tpl->block('block_autorizacao');
        }

        // votacoes
        $sessao->countVotacao = count($sessao->ownVotacao);
        if ($aba == 'votacoes' or $aba == '') {
            $tpl->addFile('votacoes', TPL . '/gerente/sessao_votacoes.html');
            foreach ($sessao->ownVotacao as $v) {
                $v->class = ($v->tipo == 'aberta') ? 'badge-success' : 'badge-warning';
                $v->alternativas = '';
                foreach ($v->ownAlternativa as $a) {
                    $v->alternativas .= $a->texto . ' | ';
                }
                $v->alternativas = substr($v->alternativas, 0, -2);
                $tpl->V = $v;

                $tpl->block('block_votacao');
            }
        }

        // Eleitores
        $sessao->countTokenAberto = count(array_keys(array_column($sessao->ownToken, 'tipo'), 'aberta'));
        if ($aba == 'eleitores') {
            $sessao->countTokenFechado = count(array_keys(array_column($sessao->ownToken, 'tipo'), 'fechada'));
            $tokensAbertos = array_filter($sessao->ownToken, function ($a) {
                return ($a->tipo == 'aberta');
            });
            $tpl->addFile('eleitores', TPL . '/gerente/sessao_eleitores.html');
            foreach ($tokensAbertos as $token) {
                $tpl->T = $token;
                $tpl->block('block_eleitor');
            }
        }
        $tpl->show('userbar');
    }

    protected function sessaoGetActions($sessao)
    {
        $endpoint = '/gerente/sessao/' . $sessao->id . '?codpes=' . SS::getUser()['codpes'];

        switch ($this->query->acao) {
            case 'exportarEleitores':
                $data['acao'] = $this->query->acao;
                $tokens = Api::send($endpoint, $data);

                header('Content-Type: application/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="eleitores.txt";');
                $f = fopen('php://output', 'w');
                foreach ($tokens as $t) {
                    fputcsv($f, [$t->apelido, $t->nome, $t->email], ';');
                }
                fclose($f);
                exit;
                break;

            case 'emailTokensControle':
                $data['acao'] = $this->query->acao;
                $ret = Api::send($endpoint, $data);
                break;

            case 'emailEleitor':
                $data['acao'] = $this->query->acao;
                $data['id'] = $this->query->id;
                $ret = Api::send($endpoint, $data);
                break;

            case 'emailTodosEleitores':
                $data['acao'] = $this->query->acao;
                $data['id'] = $this->query->id;
                $ret = Api::send($endpoint, $data);
                break;

            case 'apagarSessao':
                $data['acao'] = $this->query->acao;
                $ret = Api::send($endpoint, $data);
                if ($ret->status == 'ok') {
                    SS::setMsg(['msg' => $ret->data, 'class' => 'alert-info',]);
                } else {
                    SS::setMsg(['msg' => $ret->data, 'class' => 'alert-danger',]);
                }
                header('Location: ' . getenv('WWWROOT'));
                exit;
                break;
        }
        // vamos mostrar a mensagem de retorno
        if ($ret->status == 'ok') {
            SS::setMsg(['msg' => $ret->data, 'class' => 'alert-info',]);
        } else {
            SS::setMsg(['msg' => $ret->data, 'class' => 'alert-danger',]);
        }
        header('Location:' . $_SERVER['REDIRECT_URL']);
        exit;
    }

    protected function sessaoPostActions($sessao)
    {
        $user = SS::get('user');
        // if (!$user) {
        //     echo json_encode(['status' => 'erro', 'msg' => 'Necessário autenticar novamente']);
        //     exit;
        // }
        $endpoint = '/gerente/sessao/' . $sessao->id . '?codpes=' . $user['codpes'];

        $data = $this->data->getData();

        $ret = Api::send($endpoint, $data, $this->files);
        if ($this->ajax) {
            echo json_encode(['status' => $ret->status, 'msg' => $ret->data]);
        } else {
            $class = $ret->status == 'erro' ? 'alert-danger' : 'alert-success';
            SS::setMsg(['class' => $class, 'msg' => $ret->data]);
            header('Location:' . $_SERVER['REDIRECT_URL']);
        }
        exit;
    }
}
