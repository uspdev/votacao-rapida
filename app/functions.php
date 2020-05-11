<?php

use \RedBeanPHP\R as R;

function generateRandomString($length = 6)
{
    return substr(str_shuffle(str_repeat($x = 'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
}

function gerarTokens($qt, $import = false)
{
    // o import = true gera um campo _type usado pelo
    // redbean para importar com dispense
    $tipos = [
        ['tipo' => 'apoio', 'qt' => 1],
        ['tipo' => 'painel', 'qt' => 1],
        ['tipo' => 'recepcao', 'qt' => 1],
        ['tipo' => 'fechada', 'qt' => $qt],
        ['tipo' => 'aberta', 'qt' => $qt],
    ];

    $tokens = [];
    foreach ($tipos as $tipo) {
        ($import) ? $token['_type'] = 'token' : '';

        $token['tipo'] = $tipo['tipo'];
        $token['nome'] = '';
        $token['ativo'] = 0;
        for ($i = 0; $i < $tipo['qt']; $i++) {
            // aqui vamos garantir tokens únicos para cada sessão
            while ($newToken = generateRandomString(6)) {
                if (!in_array($newToken, array_column($tokens, 'token'))) {
                    $token['token'] = $newToken;
                    break;
                }
                //echo 'gerou repetido! ', $newToken,PHP_EOL;exit;
            }
            $tokens[] = $token;
        }
    }
    return $tokens;
}

function gerarListaQrcodePdf($sessao)
{
    $filename = $sessao->hash . '_qrcodes.pdf';
    $sessao->tokens_pdf = $filename;
    R::store($sessao);

    $tokens = $sessao->ownTokenList;

    $tpl = new raelgc\view\Template(TPL . '/qrcode/instrucoes.html');
    $tpl->addFile('qrcode_lista', TPL . '/qrcode/lista.html');
    $tpl->style = file_get_contents(TPL . '/qrcode/style.html');

    $tpl->wwwroot = getenv('WWWROOT');
    $tpl->S = $sessao;

    // para as instruções somente
    foreach ($tokens as $token) {
        switch ($token->tipo) {
            case 'apoio':
                $tpl->token_apoio = $token->token;
                break;
            case 'painel':
                $tpl->token_painel = $token->token;
                break;
            case 'recepcao':
                $tpl->token_recepcao = $token->token;
                break;
        }
    }

    $tpl->logo_usp = ROOTDIR . '/public/media/usp-logo.png';
    $tpl->S = $sessao;


    // para cedulas de tokens
    $tpl->datahora = date('d/m/Y H:i:s');

    foreach ($tokens as $token) {
        $qrcode = renderCedula($sessao, $token);
        $tpl->qrcode = $qrcode;
        next($tokens) ? $tpl->block('bloco_next') : '';
        $tpl->block('block_votacao');

        $tpl2 = new raelgc\view\Template(TPL . '/qrcode/individual.html');
        $tpl2->style = file_get_contents(TPL . '/qrcode/style.html');
        $tpl2->qrcode = $qrcode;
        $content = $tpl2->parse();

        if (!empty($token->nome)) {
            $filename2 = $sessao->hash . '-' . $token->tipo . '-' . $token->nome . '.pdf';
        } else {
            $filename2 = $sessao->hash . '-' . $token->tipo . '-' . $token->token . '.pdf';
        }
        if ($token->tipo == 'aberta') {
            geraPdf($content, $filename2, 'individual');
        }
    }

    $content = $tpl->parse();
    geraPdf($content, $filename, 'lista');

    // para cedulas individuais
}

function geraPdf($content, $filename, $tipo = 'lista')
{
    // agora que temos o content vamos gerar o PDF
    try {
        if ($tipo == 'lista') {
            $html2pdf = new Spipu\Html2Pdf\Html2Pdf('P', 'A4', 'en', true, 'UTF-8', array(0, 0, 0, 0));
        } else {
            $html2pdf = new Spipu\Html2Pdf\Html2Pdf('L', [210, 70], 'en', true, 'UTF-8', array(0, 0, 0, 0));
        }
        $html2pdf->pdf->SetDisplayMode('fullpage');
        $html2pdf->writeHTML($content);
        $html2pdf->output(ARQ . '/' . $filename, 'F');
    } catch (Spipu\Html2Pdf\Exception\Html2PdfException $e) {
        $html2pdf->clean();
        $formatter = new Spipu\Html2Pdf\Exception\ExceptionFormatter($e);
        echo $formatter->getHtmlMessage();
    }
}

function renderCedula($sessao, $token)
{
    $tpl = new raelgc\view\Template(TPL . '/qrcode/cedula_qrcode.html');

    $tpl->S = $sessao;
    $tpl->T = $token;
    $tpl->logo_usp = ROOTDIR . '/public/media/usp-logo.png';
    $tpl->qrcode = getenv('WWWROOT') . '/' . $sessao->hash . '/' . $token->token;

    ($sessao->logo) ? $tpl->block('block_logo') : '';
    ($token->tipo == 'aberta') ? $tpl->block('block_nome') : '';

    return $tpl->parse();
}
