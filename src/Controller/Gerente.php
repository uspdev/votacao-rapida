<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;
use Uspdev\Votacao\Email;

class Gerente
{
    public function sessao($id)
    {
        //$query = \Flight::request()->query;
        if (empty($this->query->codpes))
            return ['status' => 'erro', 'msg' => 'Sem usuário codpes'];

        SELF::db();

        $usuario = R::findOne('usuario', 'codpes = ?', [$this->query->codpes]);
        if (!$usuario) {
            return ['status' => 'erro', 'msg' => 'Usuário inválido'];
        }

        if ($id == 0) {
            // vamos criar nova sessão
            $sessao = SELF::criarSessao($usuario);
        } else {
            // ou obter existente
            $sessao = $this->obterSessao($id, $usuario);
            if (!$sessao) {
                return ['status' => 'erro', 'msg' => 'Sessão inexistente ou sem acesso'];
            }
        }

        if ($this->method == 'POST') {
            switch ($this->data->acao) {
                case 'addUser':
                    $novo_usuario = R::findOrCreate('usuario', [
                        'codpes' => $this->data->codpes,
                    ]);
                    $sessao->sharedUsuarioList[] = $novo_usuario;
                    R::store($sessao);
                    return ['status' => 'ok', 'data' => json_encode($usuario->export())];
                    break;
                case 'delUser':
                    $usuario = $sessao->withCondition('usuario.id = ? ', [$this->data->id])->sharedUsuarioList;
                    unset($sessao->sharedUsuarioList[key($usuario)]);
                    R::store($sessao);
                    return $usuario;
                    break;
                case 'emailTeste':
                    $dest = $this->data->dest;
                    $data = Email::send([
                        'destinatario' => $dest,
                        'assunto' => 'Credenciais de votação: ' . $sessao->nome . ' - data hora - ' . generateRandomString(4),
                        'mensagem' => file_get_contents(TPL . '/email/teste.html'),
                        'responderPara' => $sessao->email,
                        'anexo1' => ROOTDIR . '/sandbox/qrcode.png',
                        //  'anexo1' => LOCAL . '/arquivos/UUZEWSRWKBXOGJVWIYJV-aberta-Masaki.pdf',
                    ]);
                    if ($data !== true) {
                        return ['status' => 'erro', 'data' => $data];
                    }
                    //print_r($data);exit;
                    return ['status' => 'ok', 'data' => 'Email enviado com sucesso.'];
                    break;
            }
            return ['status' => 'erro', 'data' => 'Sem ação para ' . $this->data->acao];
        }

        $sessao->apoio = R::findOne('token', "tipo = 'apoio' and sessao_id = ?", [$sessao->id]);
        $sessao->painel = R::findOne('token', "tipo = 'painel' and sessao_id = ?", [$sessao->id]);
        $sessao->recepcao = R::findOne('token', "tipo = 'recepcao' and sessao_id = ?", [$sessao->id]);

        $sessao->sharedUsuarioList;
        return $sessao;
    }

    protected static function criarSessao($usuario)
    {
        $sessao = R::dispense('sessao');
        $sessao->sharedUsuarioList[] = $usuario;
        $sessao->nome = 'Nova sessão criada em ' . date('d/m/Y H:i:s');
        $sessao->hash = generateRandomString(20);
        $sessao->unidade = $usuario->unidade;
        $sessao->email = $usuario->email;
        $sessao->ano = date('Y');
        $sessao->estado = 'Em elaboração';
        $sessao->link = getenv('WWWROOT') . '/' . $sessao->hash;
        $id = R::store($sessao);

        $sessao = R::load('sessao', $id);
        Token::gerarTokensControle($sessao);

        return $sessao;
    }

    protected static function obterSessao($sessao_id, $usuario)
    {
        if ($usuario->codpes == '1575309') {
            $sessao = R::load('sessao', $sessao_id);
        } else {
            $sessoes = $usuario->withCondition('sessao.id = ?', [$sessao_id])->sharedSessaoList;
            if (empty($sessoes)) {
                return false;
            }
            $sessao = array_pop($sessoes);
        }
        return $sessao;
    }

    public function listarSessoes()
    {
        if (empty($this->query->codpes)) return 'sem codpes';

        SELF::db();
        $usuario = R::findOne('usuario', 'codpes = ?', [$this->query->codpes]);
        if ($usuario->codpes == '1575309') {
            $sessoes = R::findAll('sessao');
        } else {
            $sessoes = $usuario->sharedSessao;
        }
        return $sessoes;
    }

    public function listarTokens($hash)
    {
        return Token::listarTokens($hash);
    }

    public function login()
    {
        $userdata = $this->data;
        SELF::db();
        if (!$user = R::findOne('usuario', 'codpes = ?', [$userdata['codpes']])) {
            $user = R::dispense('usuario');
        };
        foreach ($userdata as $key => $val) {
            $user->$key = $val;
        }
        $user->lastlogin = date('"Y-m-d H:i:s"');
        R::store($user);
        return $user;
    }

    protected static function db()
    {
        R::selectDatabase('votacao');
    }
}
