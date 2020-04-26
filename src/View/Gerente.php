<?php

namespace Uspdev\Votacao\View;

use Uspdev\Votacao\Api;
use Uspdev\Votacao\Template;
use Uspdev\Votacao\SessaoPHP as SS;
use Uspdev\Senhaunica\Senhaunica;
use Uspdev\Votacao\Mail;



class Gerente
{
    // index
    public static function index()
    {
        if (empty($user = SS::get('user'))) {
            // se nao estiver logado
            SELF::ajuda('');
        }

        // buscar as sessões desse usuário
        // if (!($user['codpes'] == '1575309' ||
        //     $user['codpes'] == '3567082' || //poliana
        //     $user['codpes'] == '4807059' || //adriana
        //     $user['codpes'] == '2508632'    //nivaldo
        // )) {
        //     SelF::ajuda('Você não tem acesso à esse sistema.');
        // }

        // usuário está ok, vamos procurar dados dele
        $tpl = new Template('gerente/index.html');

        // buscar as sessões desse usuário
        $sessoes = Api::send('/gerente/listarSessoes?codpes=' . $user['codpes']);
        foreach ($sessoes as $sessao) {
            $tpl->S = $sessao;
            $tpl->block('block_sessao');
        }

        //$tpl->block('block_topo_img');

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

    public function sessao($id)
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

        if (!empty($this->query->acao)) {
            switch ($this->query->acao) {
                case 'email_teste':
                    $data['acao'] = 'emailTeste';
                    $data['dest'] = 'kawabata@sc.usp.br';
                    $ret = Api::send($endpoint, $data);
                    if ($ret->status == 'ok') {
                        SS::setMsg([
                            'msg' => $ret->data,
                            'class' => 'alert-info',
                        ]);
                    }

                    header('Location:' . $_SERVER['REDIRECT_URL']);
                    exit;
                    break;
            }
        }


        // echo '<pre>';
        // print_r($sessao);
        // exit;

        $tpl = new Template('gerente/sessao.html');
        $tpl->S = $sessao;

        if (!empty($sessao->status) && $sessao->status == 'erro') {
            Template::erro($sessao->msg);
        }

        // autorizacao
        $tpl->addFile('autorizacao', TPL . '/gerente/sessao_autorizacao.html');
        $usuarios = $sessao->sharedUsuario;
        foreach ($usuarios as $u) {
            $u->self = ($u->codpes == $user['codpes']) ? 'self' : '';
            $tpl->U = $u;
            $tpl->block('block_autorizacao');
        }

        // votacoes


        $tpl->show('userbar');
    }

    public function sessaoPost($id)
    {
        $user = SS::get('user');
        if (!$user) {
            echo json_encode(['status' => 'erro', 'msg' => 'Necessário autenticar novamente']);
            exit;
        }
        $data = $this->data->getData();
        $ret = Api::send('/gerente/sessao/' . $id . '?codpes=' . $user['codpes'], $data);
        if ($this->ajax) {
            echo json_encode(['status' => 'ok', 'msg' => $ret]);
        } else {
            header('Location:' . getenv('WWWROOT') . '/gerente/' . $id);
        }
        exit;
    }

    // public function email($sessao, $dest)
    // {
    //     //$tpl = new Template(TPL./)
    //     Mail::send(
    //         [
    //             'destinatario' => $dest,
    //             'assunto' => 'Credenciais: '. $sessao->nome. ' - data hora - ' . generateRandomString(4),
    //             'mensagem' => file_get_contents(TPL . '/email/teste.html'),
    //             'responderPara' => $sessao->email,
    //             'anexo1' => ROOTDIR . '/sandbox/qrcode.png',
    //           //  'anexo1' => LOCAL . '/arquivos/UUZEWSRWKBXOGJVWIYJV-aberta-Masaki.pdf',
    //         ]
    //     );
    // }
}
