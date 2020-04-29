<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;
use Uspdev\Votacao\Email;

class Gerente
{
    public function sessao($id)
    {
        if (empty($this->query->codpes)) {
            return ['status' => 'erro', 'msg' => 'Sem usuário codpes'];
        }
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
                    return ['status' => 'ok', 'data' => 'Usuário adicionado com sucesso.'];
                    break;
                case 'delUser':
                    $usuario = $sessao->withCondition('usuario.id = ? ', [$this->data->id])->sharedUsuarioList;
                    unset($sessao->sharedUsuarioList[key($usuario)]);
                    R::store($sessao);
                    return ['status' => 'ok', 'data' => 'Usuário removido com sucesso.'];
                    break;
                case 'emailTokensControle':
                    $data = Email::sendControle($sessao);
                    if ($data !== true) {
                        return ['status' => 'erro', 'data' => $data];
                    }
                    return ['status' => 'ok', 'data' => 'Email de controle enviado com sucesso.'];
                    break;

                case 'emailEleitor':
                    $token = R::load('token', $this->data->id);
                    $data = Email::sendVotacao($sessao, $token, true);
                    if ($data !== true) {
                        return ['status' => 'erro', 'data' => $data];
                    }
                    return ['status' => 'ok', 'data' => 'Email de eleitor enviado com sucesso.'];
                    break;

                case 'emailTodosEleitores':
                    $data = Email::sendTodosVotacao($sessao);
                    if ($data == false) {
                        return ['status' => 'erro', 'data' => $data];
                    }
                    return ['status' => 'ok', 'data' => 'Fila de envio: sucesso: ' . $data[0] . ', erro: ' . $data[1]];
                    break;

                case 'atualizarSessao':
                    foreach ($this->data as $key => $val) {
                        if (in_array($key, ['nome', 'unidade', 'ano', 'estado', 'logo', 'link', 'email', 'quando'])) {
                            $sessao->$key = $val;
                        }
                    }
                    R::store($sessao);
                    return ['status' => 'ok', 'data' => 'Dados atualizados com sucesso.'];
                    break;

                case 'atualizarVotacao':
                    $votacao = array_pop($sessao->withCondition('id = ?', [$this->data->id])->ownVotacao);
                    if ($votacao) {
                        foreach ($this->data as $key => $val) {
                            if (in_array($key, ['nome', 'descricao'])) {
                                $votacao->$key = trim($val);
                            }
                        }
                        R::store($votacao);
                        return ['status' => 'ok', 'data' => 'Dados atualizados com sucesso.'];
                    }
                    return ['status' => 'erro', 'data' => 'Votação id=' . $this->data->id . ' não encontrada.'];

                    break;

                case 'apagarSessao':
                    $ret = SELF::apagarSessao($sessao);
                    return ['status' => 'ok', 'data' => 'Sessão excluída com sucesso.'];
                    break;

                case 'removerEleitor':
                    $id = $this->data->id;
                    R::exec('DELETE FROM token WHERE id = ?', [$id]);
                    //$ret = SELF::apagarSessao($sessao);
                    return ['status' => 'ok', 'data' => 'Eleitor excluído com sucesso.'];
                    break;

                case 'importarEleitores':
                    $arq = $this->files['arq_eleitores'];
                    ini_set('auto_detect_line_endings', TRUE);
                    $handle = fopen($arq['tmp_name'], 'r');
                    $eleitores = [];
                    while (($eleitor = fgetcsv($handle, 1000, ';')) !== false) {
                        Token::adiconarTokenAberto($sessao, $eleitor);
                    }
                    fclose($handle);
                    unlink($arq['tmp_name']);
                    return ['status' => 'ok', 'data' => 'Arquivo carregado com sucesso.'];
                    break;
            }
            return ['status' => 'erro', 'data' => 'Sem ação para ' . $this->data->acao];
        }

        $sessao->apoio = R::findOne('token', "tipo = 'apoio' and sessao_id = ?", [$sessao->id]);
        $sessao->painel = R::findOne('token', "tipo = 'painel' and sessao_id = ?", [$sessao->id]);
        $sessao->recepcao = R::findOne('token', "tipo = 'recepcao' and sessao_id = ?", [$sessao->id]);

        $sessao->sharedUsuarioList;
        $sessao->ownVotacaoList;
        $sessao->with('ORDER BY apelido')->ownTokenList;
        return $sessao;
    }

    protected static function apagarSessao($sessao)
    {
        // vamos limpar tokens
        R::trashAll($sessao->ownTokenList);

        $votacoes = $sessao->ownVotacaoList;
        foreach ($votacoes as $votacao) {
            // alternativas e respostas
            R::trashAll($votacao->ownAlternativaList);
            R::trashAll($votacao->ownRespostaList);
        }
        // votacoes
        R::trashAll($votacoes);

        // arquivos
        exec('rm ' . ARQ . '/' . $sessao->hash . '*.pdf', $out, $ret);

        // e finalmente a sessao
        R::trash($sessao);
        return true;
    }

    protected static function criarSessao($usuario)
    {
        $sessao = R::dispense('sessao');
        $sessao->unidade = $usuario->unidade;
        $sessao->ano = date('Y');
        $sessao->nome = 'Nova sessão criada em ' . date('d/m/Y H:i:s');
        $sessao->quando = date('d/m/Y');
        $sessao->hash = generateRandomString(20);
        $sessao->estado = 'Em elaboração';
        $sessao->email = $usuario->email;
        $sessao->logo = '';
        $sessao->link = '';
        $sessao->sharedUsuarioList[] = $usuario;
        $id = R::store($sessao);

        //$sessao = R::load('sessao', $id);
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
        if (!$sessoes) {
            $sessoes = ['status' => 'ok', 'data' => 'Sem sessões para listar'];
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
