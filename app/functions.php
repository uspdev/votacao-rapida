<?php

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
        if ($import) {
            $token['_type'] = 'token';
        }
        $token['tipo'] = $tipo['tipo'];
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

function gerarListaQrcodePdf($sessao, $logo2)
{
    $filename = $sessao->hash . '_qrcodes.pdf';

    $tpl = new raelgc\view\Template(ROOTDIR . '/template/qrcode/instrucoes.html');
    $tpl->addFile('qrcode_lista', ROOTDIR . '/template/qrcode/lista.html');

    $tpl->nome = $sessao->nome;
    $tpl->logo1 = ROOTDIR . '/template/qrcode/logo_usp.png';

    $tpl->link = $sessao->link_manual;
    $tpl->datahora = date('d/m/Y H:i:s');

    $tokens = $sessao->ownTokenList;
    foreach ($tokens as $token) {

        $tpl->token = $token->token;
        $tpl->qrcode = getenv('WWWROOT') . '/' . $sessao->hash . '/' . $token->token;

        // vamos colocar um espaço para centralizar o texto
        if ($token->tipo == 'aberta') {
            $tpl->tipo = '&nbsp;' . strtoupper($token->tipo);
        } else {
            $tpl->tipo = strtoupper($token->tipo);
        }

        // se foi passado logo2 vamos inserir
        if ($logo2) {
            $tpl->logo2a = '<img src="' . $logo2 . '" style="height: 60px">'; //nas instruçoes
            $tpl->logo2 = '<img src="' . $logo2 . '" class="logo">'; //na lista
            //$tpl->block('bloco_logo2');
        }

        if (next($tokens)) {
            $tpl->block('bloco_next');
        }

        $tpl->block('block_votacao');
    }
    $content = $tpl->parse();

    try {
        $html2pdf = new Spipu\Html2Pdf\Html2Pdf('P', 'A4', 'en');
        $html2pdf->pdf->SetDisplayMode('fullpage');
        $html2pdf->writeHTML($content);
        $html2pdf->output(ARQ . '/' . $filename, 'F');
    } catch (Spipu\Html2Pdf\Exception\Html2PdfException $e) {
        $html2pdf->clean();
        $formatter = new Spipu\Html2Pdf\Exception\ExceptionFormatter($e);
        echo $formatter->getHtmlMessage();
    }

    return $filename;
}

function obterSessao($hash, $token)
{
    $url = API . '/run/' . $hash . '/' . $token;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, 'admin:admin');
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $sessao = curl_exec($ch);
    curl_close($ch);

    $obj = json_decode($sessao);
    if (!is_object($obj)) {
        echo 'Mensagem ao tentar obter sessão: ', $sessao;
        exit;
    }

    return $obj;
}

function post($hash, $token, $data)
{
    $url = API . '/run/' . $hash . '/' . $token;
    $headers = ['Content-Type: application/json', 'user-agent: mock data votacao v1.0'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, 'admin:admin');
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $return = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($return);
    return $json ? $json : $return;
}
