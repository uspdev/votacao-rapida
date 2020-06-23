<?php

namespace Uspdev\Votacao\Controller;

use \RedBeanPHP\R as R;
use Uspdev\Votacao\Model\Email;
use Uspdev\Votacao\Model\Token;
use Uspdev\Votacao\Model\Sessao;
use Uspdev\Votacao\Model\Votacao;
use Uspdev\Votacao\Model\Usuario;
use Uspdev\Votacao\Model\Log;

class Gerente
{
    public function __construct()
    {
        R::selectDatabase('votacao');
    }

    public function sessao($id)
    {
        // a autenticação da api já foi tratada antes
        if (empty($this->query->codpes)) {
            return ['status' => 'erro', 'msg' => 'Sem usuário codpes'];
        }

        $usuario = Usuario::obter($this->query->codpes);

        if ($id == '0' && !empty($this->data->acao)  && $this->data->acao == 'adicionarSessao') {
            // vamos criar nova sessão com dados do post adicionarSessao
            $ns = Sessao::adicionar($usuario, $this->data);
            Log::sessao('sucesso adicionarSessao', [
                'id' => $ns->id,
                'nome' => $ns->id,
                'hash' => $ns->hash,
                'usr_codpes' => $usuario->codpes,
                'usr_nome' => $usuario->nome
            ]);

            return ['status' => 'ok', 'data' => $ns->id];
        } else {
            // ou obter existente e validando o usuario
            $sessao = Sessao::obterPorId($id, $usuario);
            if (!$sessao) {
                return ['status' => 'erro', 'msg' => 'Sessão inexistente ou sem acesso'];
            }
        }

        if ($this->method == 'POST') {
            $acao = $this->data->acao;
            switch ($acao) {
                case 'adicionarGerente':
                    if (!is_numeric($this->data->codpes)) {
                        return ['status' => 'erro', 'data' => 'Número USP informado está mal formado.'];
                    }
                    $novo_gerente = Usuario::obterOuCriar($this->data->codpes);
                    $novo_gerente->noLoad()->sharedSessaoList[] = $sessao;
                    Log::sessao('sucesso adicionarGerente', [
                        'sessao_id' => $sessao->id,
                        'codpes' => $novo_gerente->codpes,
                        'nome' => $novo_gerente->nome,
                        'usr_codpes' => $usuario->codpes,
                        'usr_nome' => $usuario->nome,
                    ]);
                    R::store($novo_gerente);
                    return ['status' => 'ok', 'data' => 'Gerente adicionado com sucesso.'];
                    break;
                case 'removerGerente':
                    if ($gerente = R::load('usuario', $this->data->id)) {
                        $sharedSessao = $gerente->withCondition('sessao_id = ?', [$sessao->id])->sharedSessaoList;
                        unset($gerente->sharedSessaoList[key($sharedSessao)]);
                        R::store($gerente);
                        Log::sessao('sucesso removerGerente', [
                            'sessao_id' => $sessao->id,
                            'codpes' => $gerente->codpes,
                            'nome' => $gerente->nome,
                            'usr_codpes' => $usuario->codpes,
                            'usr_nome' => $usuario->nome,
                        ]);
                        return ['status' => 'ok', 'data' => 'Gerente removido com sucesso.'];
                    } else {
                        return ['status' => 'erro', 'data' => 'Dados de gerente mal formados.'];
                    }
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
                    if (empty($this->data->id)) {
                        return ['status' => 'erro', 'data' => 'Eleitor não existe nessa sessão'];
                    }
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
                    if (empty($this->data->id)) {
                        Log::votacao(
                            'erro editarVotacao',
                            [
                                'sessao_id' => $sessao->id,
                                'err_msg' => 'id de votação inexistente',
                                'usr_msg' => 'Dados de editar votação mal formados',
                                'codpes' => $usuario->codpes,
                                'usr_name' => $usuario->nome
                            ]
                        );
                        return ['status' => 'erro', 'data' => 'Dados de editar votação mal formados'];
                    }
                    $votacao = array_pop($sessao->withCondition('id = ?', [$this->data->id])->ownVotacao);
                    if (!$votacao) {
                        Log::votacao(
                            'erro editarVotacao',
                            [
                                'sessao_id' => $sessao->id,
                                'usr_msg' => 'Votação id=' . $this->data->id . ' não encontrada.',
                                'codpes' => $usuario->codpes,
                                'usr_name' => $usuario->nome
                            ]
                        );
                        return ['status' => 'erro', 'data' => 'Votação id=' . $this->data->id . ' não encontrada.'];
                    }
                    return Votacao::editar($votacao, $this->data);
                    break;

                case 'adicionarVotacao':
                    return Votacao::adicionar($sessao, $this->data);
                    break;

                case 'removerVotacao':
                    if (empty($this->data->id)) {
                        Log::votacao(
                            'erro removerVotacao',
                            [
                                'sessao_id' => $sessao->id,
                                'err_msg' => 'id de votação inexistente',
                                'usr_msg' => 'Dados de remover votação mal formados',
                                'codpes' => $usuario->codpes,
                                'usr_name' => $usuario->nome
                            ]
                        );
                        return ['status' => 'erro', 'data' => 'Dados de remover votação mal formados'];
                    }
                    return Votacao::remover($this->data->id);
                    break;

                case 'atualizarSessao':
                    if ($ret = Sessao::atualizar($sessao, $this->data)) {
                        return ['status' => 'ok', 'data' => 'Dados atualizados com sucesso.'];
                    } else {
                        return ['status' => 'erro', 'data' => $ret];
                    }
                    break;

                case 'removerSessao':
                    Log::sessao('sucesso removerSessao', [
                        'id' => $sessao->id, 'hash' => $sessao->hash,
                        'usr_codpes' => $usuario->codpes, 'usr_nome' => $usuario->nome,
                    ]);
                    $ret = Sessao::remover($sessao);
                    return ['status' => 'ok', 'data' => 'Sessão removida com sucesso.'];
                    break;
            }
            return ['status' => 'erro', 'data' => 'Sem ação para ' . $this->data->acao];
        }

        $sessao->apoio = R::findOne('token', "tipo = 'apoio' and sessao_id = ?", [$sessao->id]);
        $sessao->painel = R::findOne('token', "tipo = 'painel' and sessao_id = ?", [$sessao->id]);
        $sessao->recepcao = R::findOne('token', "tipo = 'recepcao' and sessao_id = ?", [$sessao->id]);

        $sessao->sharedUsuarioList;
        // vamos buscar as alternativas também
        foreach ($sessao->with('ORDER BY ordem')->ownVotacaoList as $v) {
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

    public function user()
    {
        // a autenticação da api já foi tratada antes
        if (empty($this->query->codpes)) {
            return ['status' => 'erro', 'msg' => 'Sem usuário codpes'];
        }

        $usuario = Usuario::obter($this->query->codpes);

        if ($this->method == 'POST') {
            $acao = $this->data->acao;
            switch ($acao) {
                case 'resetarAviso':
                    $usuario->ultimoAviso = (int) $this->data->ultimo_aviso;
                    R::store($usuario);
                    return ['status' => 'ok', 'data' => 'Avisos arquivados com sucesso.'];
                    break;
            }
        }
    }

    public function listarSessao()
    {
        $usuario = Usuario::obter($this->query->codpes);
        return Sessao::listar($usuario);
    }

    // public function listarResposta($votacao_id)
    // {
    //     $votacao = R::load('votacao', $votacao_id);
    //     return Votacao::listarResposta($votacao);
    // }

    // usado somente para testes pois é chamado internamente
    public function exportarVotacao($votacao_id)
    {
        $votacao = R::load('votacao', $votacao_id);
        return Votacao::exportar($votacao);
    }

    // usado no demo somente
    public function listarTokens($hash)
    {
        return Token::listar($hash);
    }

    public function nologin()
    {
        $userdata = $this->data;
        if (empty($userdata['codpes'])) {
            return ['status' => 'erro', 'data' => 'Faltando dados do usuario'];
        }
        Log::auth('user denied', $userdata->getData());
        return ['status' => 'ok', 'data' => 'Log registrado com sucesso'];
    }

    public function login()
    {
        $userdata = $this->data;
        if (empty($userdata['codpes'])) {
            return ['status' => 'erro', 'data' => 'Faltando dados do usuario'];
        }
        //SELF::db();
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
}
