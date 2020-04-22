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

        // buscar as sessões desse usuário
        if (!($user['codpes'] == '1575309' ||
            $user['codpes'] == '3567082' || //poliana
            $user['codpes'] == '4807059' || //adriana
            $user['codpes'] == '2508632'    //nivaldo
        )) {
            SelF::ajuda('Você não tem acesso à esse sistema.');
        }

        // usuário está ok, vamos procurar dados dele
        $tpl = new Template('index.html');

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

    public static function login()
    {
        $auth = new Senhaunica([
            'consumer_key' => getenv('CONSUMER_KEY'),
            'consumer_secret' => getenv('CONSUMER_SECRET'),
            'callback_id' => getenv('SENHA_UNICA_CALLBACK_ID'), // callback_id é o sequencial no servidor
            'amb' => getenv('SENHA_UNICA_AMB'), // 'dev' = teste, 'prod' = producao
        ]);

        $res = $auth->login();
        $vinculo = $auth->obterVinculo('tipoVinculo', ['SERVIDOR', 'Estagiário']);
        if ($vinculo) {
            $user['codpes'] = $res['loginUsuario'];
            $user['nome'] = $res['nomeUsuario'];
            $user['email'] = $res['emailPrincipalUsuario'];
            $user['unidade'] = $vinculo['siglaUnidade'];

            $usr = Api::send('/gerente/login', $user);
            SS::set('user', $user);
        }
        header('Location:' . getenv('WWWROOT'));
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

        //$tpl->block('block_topo_img');

        $tpl->show('userbar');
        exit;
    }

    public static function sessao($id)
    {
        $codpes = SS::getUser();
        $sessao = Api::send('/gerente/sessao/' . $id . '?codpes=' . $codpes);

        $tpl = new Template('gerente/sessao.html');
        $tpl->S = $sessao;

        $usuarios = $sessao->sharedUsuario;
        foreach ($usuarios as $u) {
            $u->self = ($u->codpes == $codpes) ? 'self' : '';
            $tpl->U = $u;
            $tpl->block('block_autorizacao');
        }

        $tpl->show('userbar');
    }

    public function sessaoPost($id)
    {
        $data = $this->data->getData();
        $codpes = SS::get('user')['codpes'];
        $ret = Api::send('/gerente/sessao/' . $id . '?codpes=' . $codpes, $data);
        if ($this->request->ajax) {
            echo json_encode(['status' => 'ok']);
        } else {
            header('Location:' . getenv('WWWROOT') . '/gerente/' . $id);
        }
        exit;
    }
}
