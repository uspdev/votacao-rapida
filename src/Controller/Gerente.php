<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;
use Uspdev\Votacao\Model\Email;
use Uspdev\Votacao\Model\Token;
use Uspdev\Votacao\Model\Sessao;
use Uspdev\Votacao\Model\Votacao;
use Uspdev\Votacao\Model\Log;

class Gerente
{
    public function sessao($id)
    {
        // a autenticação da api já foi tratada antes
        if (empty($this->query->codpes)) {
            return ['status' => 'erro', 'msg' => 'Sem usuário codpes'];
        }
        SELF::db();

        $usuario = R::findOne('usuario', 'codpes = ?', [$this->query->codpes]);
        if (!$usuario) {
            return ['status' => 'erro', 'msg' => 'Usuário inválido'];
        }

        if ($id == '0' && $this->data->acao == 'criarSessao') {
            // vamos criar nova sessão com dados do post

            $ns = Sessao::criar($usuario, $this->data);
            Log::sessao('Criar sessão', [
                'sessao_id' => $ns->id, 'hash' => $ns->hash,
                'usuario-codpes' => $usuario->codpes, 'usuario-nome' => $usuario->nome
            ]);

            return ['status' => 'ok', 'data' => $ns->id];
        } else {
            // ou obter existente
            $sessao = Sessao::obterPorId($id, $usuario);
            if (!$sessao) {
                return ['status' => 'erro', 'msg' => 'Sessão inexistente ou sem acesso'];
            }
        }

        if ($this->method == 'POST') {
            $acao = $this->data->acao;
            switch ($acao) {
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

                case 'importarEleitores':
                    $arq = $this->files['arq_eleitores'];
                    ini_set('auto_detect_line_endings', TRUE);
                    $handle = fopen($arq['tmp_name'], 'r');
                    $s = 0;
                    $r = 0;
                    $t = 0;
                    while (($line = fgets($handle)) !== false) {
                        if (!empty(trim($line))) {
                            if (mb_detect_encoding($line, 'UTF-8, ISO-8859-1', true) != 'UTF-8') {
                                $line = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1');
                            }
                            $eleitor = str_getcsv($line, ';');
                            $res = Token::adicionarTokenAberto($sessao, ['apelido' => substr($eleitor[0], 0, 20), 'nome' => $eleitor[1], 'email' => $eleitor[2]]);
                            ($res) ? $s++ : $r++;
                            $t++;
                        }
                    }
                    fclose($handle);
                    unlink($arq['tmp_name']);
                    return ['status' => 'ok', 'data' => "Resultado da importação: sucesso: $s, repetido: $r, total: $t"];
                    break;

                case 'exportarEleitores':
                    return $sessao->withCondition('tipo = ?', ['aberta'])->ownTokenList;
                    break;

                case 'adicionarEleitor':
                    //vamos considerar somente as chaves especificadas
                    $eleitor = array_intersect_key($this->data->getData(), array_flip(['apelido', 'nome', 'email']));

                    if (empty($eleitor['apelido']) or empty($eleitor['nome'])) {
                        return ['status' => 'erro', 'data' => 'Todos os campos são obrigatórios'];
                    }
                    if (!filter_var($eleitor['email'], FILTER_VALIDATE_EMAIL)) {
                        return ['status' => 'erro', 'data' => 'Email mal formado'];
                    }

                    if (Token::adicionarTokenAberto($sessao, $eleitor)) {
                        return ['status' => 'ok', 'data' => 'Eleitor inserido com sucesso.'];
                    } else {
                        return ['status' => 'erro', 'data' => 'Eleitor já existe'];
                    }
                    break;

                case 'removerEleitor':
                    if (Token::removerTokenAberto($sessao, $this->data->id)) {
                        return ['status' => 'ok', 'data' => 'Eleitor excluído com sucesso.'];
                    } else {
                        // aqui só deve acontecer se usuário injetar $id inexistente
                        return ['status' => 'erro', 'data' => 'Eleitor não existe nessa sessão'];
                    }
                    break;

                case 'editarEleitor':
                    $id = $this->data->id;
                    $token = R::findOne('token', 'id = ?', [$id]);
                    $token->import($this->data, 'apelido, nome, email');
                    R::store($token);
                    return ['status' => 'ok', 'data' => 'Eleitor atualizado com sucesso.'];
                    break;

                case 'editarVotacao':
                    $votacao = array_pop($sessao->withCondition('id = ?', [$this->data->id])->ownVotacao);
                    if ($votacao) {
                        if ($ret = Votacao::atualizar($votacao, $this->data)) {
                            return ['status' => 'ok', 'data' => 'Votação atualizada com sucesso.'];
                        } else {
                            return ['status' => 'erro', 'data' => 'Impossível editar uma votação que já foi votada'];
                        }
                    }
                    return ['status' => 'erro', 'data' => 'Votação id=' . $this->data->id . ' não encontrada.'];
                    break;

                case 'adicionarVotacao':
                    if ($ret = Votacao::adicionar($sessao, $this->data)) {
                        return ['status' => 'ok', 'data' => 'Votação adicionada com sucesso.'];
                    }
                    return ['status' => 'erro', 'data' => $ret];
                    break;

                case 'removerVotacao':
                    if ($ret = Votacao::remover($this->data->id)) {
                        return ['status' => 'ok', 'data' => 'Votação removida com sucesso.'];
                    } else {
                        return ['status' => 'erro', 'data' => 'Impossível remover uma votação que já foi votada'];
                    }
                    break;

                case 'atualizarSessao':
                    if ($ret = Sessao::atualizar($sessao, $this->data)) {
                        return ['status' => 'ok', 'data' => 'Dados atualizados com sucesso.'];
                    } else {
                        return ['status' => 'erro', 'data' => $ret];
                    }
                    break;

                case 'apagarSessao':
                    Log::sessao('Remover sessão', [
                        'sessao_id' => $sessao->id, 'hash' => $sessao->hash,
                        'usuario-codpes' => $usuario->codpes, 'usuario-nome' => $usuario->nome,
                    ]);

                    $ret = Sessao::remover($sessao);
                    return ['status' => 'ok', 'data' => 'Sessão excluída com sucesso.'];
                    break;
            }
            return ['status' => 'erro', 'data' => 'Sem ação para ' . $this->data->acao];
        }

        $sessao->apoio = R::findOne('token', "tipo = 'apoio' and sessao_id = ?", [$sessao->id]);
        $sessao->painel = R::findOne('token', "tipo = 'painel' and sessao_id = ?", [$sessao->id]);
        $sessao->recepcao = R::findOne('token', "tipo = 'recepcao' and sessao_id = ?", [$sessao->id]);

        $sessao->sharedUsuarioList;
        // vamos buscar as alternativas também
        foreach ($sessao->ownVotacaoList as $v) {
            // e respostas se houver
            foreach ($v->ownAlternativaList as $a) {
                $q = 'SELECT count(id) as total
                FROM resposta
                WHERE alternativa_id = ? AND last = 1';
                $a->votos = R::getCell($q, [$a->id]);
            };

            // vamos obter o total de votos computados
            $v->computados = Votacao::contarResposta($v);
        }

        $sessao->with('ORDER BY apelido')->ownTokenList;
        return $sessao;
    }

    public function listarSessoes()
    {
        SELF::db();
        $usuario = R::findOne('usuario', 'codpes = ?', [$this->query->codpes]);
        if (!$usuario) {
            return ['status' => 'erro', 'msg' => 'Usuário inválido'];
        }
        return Sessao::listar($usuario);
    }

    public function listarResposta($votacao_id)
    {
        SELF::db();
        $votacao = R::load('votacao', $votacao_id);
        return Votacao::listarResposta($votacao);
    }

    public function exportarVotacao($votacao_id)
    {
        SELF::db();
        $votacao = R::load('votacao', $votacao_id);
        return Votacao::exportar($votacao);
    }

    public function listarTokens($hash)
    {
        SELF::db();
        return Token::listar($hash);
    }

    public function nologin()
    {
        $userdata = $this->data;
        Log::auth('user denied', $userdata->getData());
        return ['status' => 'ok', 'data' => 'Log registrado com sucesso'];
    }

    public function login()
    {
        $userdata = $this->data;
        SELF::db();
        if (!$user = R::findOne('usuario', 'codpes = ?', [$userdata['codpes']])) {
            $user = R::dispense('usuario');
        };
        Log::auth('user login', $userdata->getData());

        foreach ($userdata as $key => $val) {
            $user->$key = $val;
        }
        $user->lastlogin = date('Y-m-d H:i:s');
        R::store($user);
        return $user;
    }

    protected static function db()
    {
        R::selectDatabase('votacao');
    }
}
