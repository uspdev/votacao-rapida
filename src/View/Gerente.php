<?php

namespace Uspdev\Votacao\View;

use Uspdev\Votacao\View\SessaoPHP as SS;
use Uspdev\Senhaunica\Senhaunica;


class Gerente
{
    public function login($cod = '')
    {
        if ($cod) {
            if (SS::isAdmin()) {
                $newUser['codpes'] = $cod;
                $usr = Api::send('/gerente/login', $newUser);
                SS::set('user', json_decode(json_encode($usr), true));
                header('Location:' . SS::getNext());
                exit;
            } else {
                return false;
            }
        }
        $auth = new Senhaunica([
            'consumer_key' => getenv('CONSUMER_KEY'),
            'consumer_secret' => getenv('CONSUMER_SECRET'),
            'callback_id' => getenv('SENHA_UNICA_CALLBACK_ID'), // callback_id é o sequencial no servidor
            'amb' => getenv('SENHA_UNICA_AMB'), // 'dev' = teste, 'prod' = producao
        ]);
        $res = $auth->login();

        $user['codpes'] = $res['loginUsuario'];
        $user['nome'] = $res['nomeUsuario'];
        $user['email'] = $res['emailPrincipalUsuario'];

        // todo: ainda tem de ajustar os vinculos permitidos
        $vinculo = $auth->obterVinculo('tipoVinculo', ['SERVIDOR', 'ESTAGIARIORH']);
        if ($vinculo) {
            $user['unidade'] = $vinculo['siglaUnidade'];
            $usr = Api::send('/gerente/login', $user);
            SS::set('user', json_decode(json_encode($usr), true));
        } else {
            $user['vinculos'] = array_column($res['vinculo'], 'tipoVinculo');
            $usr = Api::send('/gerente/nologin', $user);
            SS::setMsg(['class' => 'alert-danger', 'msg' => 'Usuário sem acesso ao sistema "Votação Rápida"']);
            header('Location: ' . getenv('WWWROOT'));
            exit;
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

    // index
    public function index()
    {
        if (empty($user = SS::get('user'))) {
            // se nao estiver logado
            Ajuda::inicio();
            exit;
        }

        $endpoint = '/gerente/listarSessoes?codpes=' . $user['codpes'];
        $sessoes = Api::send($endpoint);

        if ($this->method == "POST" && $this->data->acao == 'criarSessao') {
            $endpoint = '/gerente/sessao/0?codpes=' . $user['codpes'];
            $data = $this->data->getData();
            $ret = Api::send($endpoint, $data);

            // se retornar erro vamos tratar
            if ($ret->status == 'erro') {
                SS::setMsg(['class' => 'alert-danger', 'msg' => $ret->data]);
                header('Location:' . $_SERVER['REDIRECT_URL']);
                exit;
            }

            // se não, vamos direcionar para a nova sessão criada
            header('Location: gerente/' . $ret->data);
            exit;
        }

        $tpl = new Template('gerente/index.html');

        // vamos listar as sessões desse usuário se houver
        if (empty($sessoes->status)) {
            foreach ($sessoes as $sessao) {
                $tpl->S = $sessao;
                $tpl->block('block_sessao');
            }
        }
        $tpl->block('block_user');
        $tpl->show('userbar');
    }

    public function sessao($id, $aba)
    {
        $user = SS::getUser();
        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user['codpes'];
        $sessao = Api::send($endpoint);

        //mostra a sessao (debug)
        //echo '<pre>';print_r($sessao);exit; 

        // as acoes de post ficam separadas para melhorar a leitura do código
        if ($this->method == "POST") {
            $this->sessaoPostActions($sessao);
        }

        // aqui vão as ações de get
        if (!empty($this->query->acao)) {
            $this->sessaoGetActions($sessao);
        }

        // se nao for informado a aba, vamos usar votações por padrão
        if ($aba == '') {
            header('Location: ' . $_SERVER['REDIRECT_URL'] . '/votacoes');
            exit;
        }

        $tpl = new Template('gerente/sessao.html');
        $tpl->S = $sessao;

        if (!empty($sessao->status) && $sessao->status == 'erro') {
            Template::erro($sessao->msg);
        }

        // template de autorização
        $tpl->addFile('autorizacao', TPL . '/gerente/sessao_autorizacao.html');
        foreach ($sessao->sharedUsuario as $u) {
            $u->self = ($u->codpes == $user['codpes']) ? 'self' : '';
            $tpl->U = $u;
            $tpl->block('block_autorizacao');
        }

        // template de votacoes
        $sessao->countVotacao = count($sessao->ownVotacao);
        if ($aba == 'votacoes' or $aba == '') {
            $tpl->addFile('votacoes', TPL . '/gerente/sessao_votacoes.html');
            foreach ($sessao->ownVotacao as $v) {
                $v->class = ($v->tipo == 'aberta') ? 'badge-success' : 'badge-warning';
                $v->alternativas = '';
                foreach ($v->ownAlternativa as $a) {
                    $v->alternativas .= $a->texto;
                    if (!empty($v->data_fim)) {
                        // se finalizou vamos mostrar a totalização nas alternativas
                        $v->alternativas .= ' (' . $a->votos . ')';
                    }
                    if (next($v->ownAlternativa)) $v->alternativas .= "\n";
                }
                $tpl->V = $v;

                $tpl->block('block_votacao');
            }
        }

        // template de Eleitores
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
        // mostrar o post antes de submeter (debug)
        //echo '<pre>';print_r($this->data->getData());exit;
        $user = SS::get('user');
        if (!$user) {
            $ret = ['status' => 'erro', 'data' => 'Necessário autenticar novamente'];
        } else {
            $endpoint = '/gerente/sessao/' . $sessao->id . '?codpes=' . $user['codpes'];
            $data = $this->data->getData();
            $ret = Api::send($endpoint, $data, $this->files);
        }

        if ($this->ajax) {
            echo json_encode(['status' => $ret->status, 'msg' => $ret->data]);
        } else {
            $class = $ret->status == 'erro' ? 'alert-danger' : 'alert-success';
            SS::setMsg(['class' => $class, 'msg' => $ret->data]);
            $redirect = (empty($_SERVER['REDIRECT_URL'])) ? '' : $_SERVER['REDIRECT_URL'];
            header('Location:' . $redirect);
        }
        exit;
    }
}
