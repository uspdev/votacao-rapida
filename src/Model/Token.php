<?php

namespace Uspdev\Votacao\Model;

use \RedBeanPHP\R as R;

class Token
{
    public static function gerarTokensControle($sessao)
    {
        $tipos = [
            ['tipo' => 'apoio', 'qt' => 1],
            ['tipo' => 'painel', 'qt' => 1],
            ['tipo' => 'recepcao', 'qt' => 1],
        ];
        return SELF::gerarTokens($sessao, $tipos);
    }

    public static function gerarTokensVotacao($sessao, $qt)
    {
        $tipos = [
            ['tipo' => 'fechada', 'qt' => $qt],
            ['tipo' => 'aberta', 'qt' => $qt],
        ];
        return SELF::gerarTokens($sessao, $tipos);
    }

    public static function adicionarTokenFechado($sessao)
    {
        while ($newToken = generateRandomString(6)) {
            $token = R::find('token', 'sessao_id = ? and token = ?', [$sessao->id, $newToken]);
            if (!$token) {
                $token = R::dispense('token');
                $token->tipo = 'fechada';
                $token->token = $newToken;
                $token->ativo = 1;
                $token->sessao_id = $sessao->id;
                R::store($token);
                break;
            }
            //echo 'gerou repetido! ', $newToken,PHP_EOL;exit;
        }
        return $token;
    }

    public static function adicionar($sessao, $eleitor)
    {
        $e = array_map('trim', $eleitor);
        //$eleitor = array_intersect_key($eleitor, array_flip(['apelido', 'nome', 'email']));

        // se já existir não cadastramos nada
        if (R::find('token', 'sessao_id = ? and (apelido = ? or nome = ? or email = ?)', [$sessao->id, $e['apelido'], $e['nome'], $e['email']])) {
            Log::sessao('erro AdicionarEleitor', [
                'sessao_id' => $sessao->id, 'msg' => 'Eleitor já existe',
                'apelido' => $eleitor['apelido'], 'nome' => $eleitor['nome'],
                'email' => $eleitor['email']
            ]);
            return ['status' => 'erro', 'data' => 'Eleitor já existe'];
        }

        // vamos inserir verificando se o token não é repetido
        while ($newToken = generateRandomString(6)) {
            $token = R::find('token', 'sessao_id = ? and token = ?', [$sessao->id, $newToken]);
            if (!$token) {
                $token = R::dispense('token');
                $token->tipo = 'aberta';
                $token->token = $newToken;
                $token->ticket = generateRandomString(25);
                $token->import($e);
                $token->ativo = 0;
                $token->sessao_id = $sessao->id;
                R::store($token);
                break;
            }
            //echo 'gerou repetido! ', $newToken,PHP_EOL;exit;
        }
        Log::sessao('sucesso AdicionarEleitor', [
            'sessao_id' => $sessao->id, 'apelido' => $token->apelido,
            'nome' => $token->nome, 'email' => $token->email, 'token' => $token->token
        ]);
        return ['status' => 'ok', 'data' => 'Eleitor inserido com sucesso.'];
    }

    public static function editar($data)
    {
        $eleitor = R::findOne('token', 'id = ?', [$data->id]);
        if (!$eleitor) {
            return ['status' => 'erro', 'data' => 'Eleitor não encontrado.'];
        }
        $sessao_id = $eleitor->sessao_id;
        //vamos procurar eleitores com $data duplicado
        $dup = R::find(
            'token',
            'sessao_id = ? and (apelido = ? or nome = ? or email = ?) and id != ?',
            [$sessao_id, $data['apelido'], $data['nome'], $data['email'], $eleitor->id]
        );
        if ($dup) {
            Log::sessao('erro editarEleitor', [
                'sessao_id' => $sessao_id, 'msg' => 'Dado de eleitor duplicado',
                'data' => $data->getData(),
                //'novo_nome' => $eleitor['nome'], 'novo_email' => $eleitor['email']
            ]);
            return ['status' => 'erro', 'data' => 'Erro na alteração: dado de eleitor duplicado.'];
        }

        $eleitor->import($data, 'apelido, nome, email');
        R::store($eleitor);
        Log::sessao('sucesso editarEleitor', [
            'sessao_id' => $sessao_id, 'token' => $eleitor->token, 'novo_apelido' => $eleitor['apelido'],
            'novo_nome' => $eleitor['nome'], 'novo_email' => $eleitor['email']
        ]);
        return ['status' => 'ok', 'data' => 'Eleitor atualizado com sucesso.'];
    }

    public static function remover($sessao, $id)
    {
        $token = array_pop($sessao->withCondition('id = ?', [$id])->ownTokenList);
        if ($token) {
            Log::sessao('sucesso removerEleitor', [
                'sessao_id' => $sessao->id, 'apelido' => $token->apelido,
                'nome' => $token->nome, 'email' => $token->email, 'token' => $token->token
            ]);
            R::trash($token);
            return ['status' => 'ok', 'data' => 'Eleitor removido com sucesso.'];
        } else {
            Log::sessao('erro removerEleitor', [
                'sessao_id' => $sessao->id, 'msg' => 'Eleitor não existe nessa sessão',
                'apelido' => $token->apelido, 'nome' => $token->nome,
                'email' => $token->email
            ]);
            return ['status' => 'erro', 'data' => 'Eleitor não existe nessa sessão'];
        }
    }

    protected static function gerarTokens($sessao, $tipos)
    {
        SELF::db();
        foreach ($tipos as $tipo) {
            while ($newToken = generateRandomString(6)) {
                $token = R::find('token', 'sessao_id = ? and token = ?', [$sessao->id, $newToken]);
                if (!$token) {
                    $token = R::dispense('token');
                    $token->tipo = $tipo['tipo'];
                    $token->token = $newToken;
                    $token->nome = '';
                    $token->apelido = '';
                    $token->email = '';
                    $token->ativo = 0;
                    $token->sessao_id = $sessao->id;
                    R::store($token);
                    break;
                }
                //echo 'gerou repetido! ', $newToken,PHP_EOL;exit;
            }
        }
        return true;
    }

    public static function listar($hash)
    {
        SELF::db();
        $sessao = R::findOne('sessao', 'hash = ?', [$hash]);
        return $sessao->ownTokenList;
    }

    public static function ObterToken($sessao, $tipo)
    {
        SELF::db();
        return R::findOne('token', 'sessao_id = ? AND tipo = ?', [$sessao->id, $tipo]);
    }

    public static function pdfTokenFechado($sessao, $token)
    {
        $qrcode = renderCedula($sessao, $token);
        $tpl2 = new \raelgc\view\Template(TPL . '/qrcode/individual.html');
        $tpl2->style = file_get_contents(TPL . '/qrcode/style.html');
        $tpl2->qrcode = $qrcode;
        $content = $tpl2->parse();

        try {
            $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('L', [210, 70], 'en', true, 'UTF-8', array(0, 0, 0, 0));

            $html2pdf->pdf->SetDisplayMode('fullpage');
            $html2pdf->writeHTML($content);
            $pdf_string = $html2pdf->output('', 'S');
        } catch (\Spipu\Html2Pdf\Exception\Html2PdfException $e) {
            $html2pdf->clean();
            $formatter = new \Spipu\Html2Pdf\Exception\ExceptionFormatter($e);
            echo $formatter->getHtmlMessage();
        }
        return $pdf_string;
    }

    protected static function db()
    {
        R::selectDatabase('votacao');
    }
}
