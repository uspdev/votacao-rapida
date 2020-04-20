<?php

namespace Uspdev\Votacao\View;

use Uspdev\Votacao\Template;
use Uspdev\Votacao\Api;

class Demo
{
    const arq_votacao = ROOTDIR . '/test/demo-votacoes.php';
    const hash = 'UUZEWSRWKBXOGJVWIYJV';

    public static function demo()
    {
        $acao = isset($_GET['acao']) ? $_GET['acao'] : '';

        switch ($acao) {

            case 'votar':
                require_once ROOTDIR . '/cli/funcoes_cli.php';
                gerarVotosAleatorios(SELF::hash);
                echo '<a href="demo/">Clique aqui para retornar</a>';
                exit;
                break;

            case 'reset':
                require_once ROOTDIR . '/cli/funcoes_cli.php';
                echo limparRespostas(SELF::hash), '<br>';
                echo importarVotacao(SELF::hash, SELF::arq_votacao), '<br>';
                echo '<A href="demo/">Clique aqui para retornar</a>';
                exit;
                break;

            case 'tokens_pdf':
                $arq = $_GET['arq'];
                header("Content-type:application/pdf");
                header("Content-Disposition:attachment;filename=tokens_qrcode.pdf");
                header('Cache-Control: public, must-revalidate, max-age=0');
                readfile(ARQ . '/' . $arq);
                exit;
                break;
        }

        $sessao = Api::obterSessao(SELF::hash, '');
        //print_r($sessao);exit;

        $tpl = new Template('demo.html');
        $tpl->block('block_topo_img');

        $tokens = Api::send('/gerente/listarTokens/' . SELF::hash);
        $tpl->S = $sessao;
        $counta = $countf = 1;
        foreach ($tokens as $token) {
            switch ($token->tipo) {
                case 'apoio':
                    $tpl->token_apoio = $token->token;
                    break;
                case 'painel':
                    $tpl->token_tela = $token->token;
                    break;
                case 'recepcao':
                    $tpl->token_recepcao = $token->token;
                    break;
                case 'fechada':
                    $tpl->token_votacao = $token->token;
                    $tpl->count = $countf;
                    $countf++;
                    $tpl->block('block_fechada');
                    break;
                case 'aberta':
                    $tpl->token_votacao = $token->token;
                    $tpl->count = $counta;
                    $counta++;
                    $tpl->block('block_aberta');
                    break;
            }
        }
        $tpl->block('block_sessao');

        // // vamos mostrar as relações entre estados e ações
        // // Estado => Ação
        // $estados = R::findAll('estado');
        // foreach ($estados as $e) {
        //     $acao_nome = '';

        //     // vamos expandir as acoes de cada estado
        //     foreach (explode(',', $e->acoes) as $acao_cod) {
        //         $acao = R::findOne('acao', 'cod = ?', [intval($acao_cod)]);
        //         $e_nome = R::getCell('SELECT nome FROM estado WHERE cod = ' . $acao->estado);
        //         $acao_nome .= $acao->nome . ' (-> ' . $e_nome . ') | ';
        //     }
        //     $e->acao_nome = substr($acao_nome, 0, -2);
        //     //$tpl->E = $e;
        //     //$tpl->block('block_estado');
        // }

        // //Ação: Estado inicial -> estado final
        // $acoes = R::find('acao', "escopo = 'apoio'");
        // foreach ($acoes as $a) {
        //     $a->estado = R::getCell('SELECT nome FROM estado WHERE cod = ' . $a->estado);
        //     $ini = R::getAll('SELECT nome FROM estado WHERE acoes LIKE ?', ["%$a->cod%"]);
        //     if (count($ini) == 1) {
        //         $a->estado_ini = $ini[0]['nome'];
        //     } else {
        //         $a->estado_ini = '';
        //         foreach ($ini as $i) {
        //             $a->estado_ini .= $i['nome'] . ', ';
        //         }
        //         $a->estado_ini = substr($a->estado_ini, 0, -2);
        //     }

        //     //$tpl->A = $a;
        //     //$tpl->block('block_acao');
        // }



        $tpl->show();
        exit;
    }
}
