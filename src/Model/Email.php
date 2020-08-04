<?php

namespace Uspdev\Votacao\Model;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use raelgc\view\Template;
use \RedBeanPHP\R as R;
use League\OAuth2\Client\Provider\Google;
use PHPMailer\PHPMailer\OAuth;

class Email
{
    public static function sendTodosVotacao($sessao)
    {
        $tokens = $sessao->withCondition("tipo = 'aberta'")->ownTokenList;
        //return $tokens;
        $count = 0;
        $erro = 0;
        foreach ($tokens as $token) {
            if (!empty($token->email)) {
                $ret = SELF::sendVotacao($sessao, $token);
                if (!$ret) {
                    $erro++;
                } else {
                    $count++;
                }
            }
        }
        exec('php ' . ROOTDIR . '/cli/processarEmailsFila.php > /dev/null &');
        return [$count, $erro];
    }

    public static function sendVotacao($sessao, $token, $now = false)
    {
        $tpl = new Template(TPL . '/email/votacao.html');

        // tem votacao fechada, então vamos gerar tickets
        $countFechada = $sessao->withCondition('tipo = ?', ['fechada'])->countOwn('votacao');

        // só vamos enviar o link fechado se houver votacao fechada e se já não tiver sido gerado
        if ($countFechada) {
            if (empty($token->ticket)) {
                $tpl->block('block_fechada_sem');
            } else {
                $sessao->link_fechado = getenv('WWWROOT') . '/' . $sessao->hash . '/' . $token->ticket;
                $tpl->block('block_fechada_com');
            }

            $tpl->block('block_fechada');
        }
        $sessao->link_direto = getenv('WWWROOT') . '/' . $sessao->hash . '/' . $token->token;
        $qrcode = SELF::qrCodePngData($sessao->link_direto);
        $tpl->S = $sessao;
        $tpl->T = $token;

        $corpo = $tpl->parse();

        $ret = SELF::adicionarFila($sessao, [
            'destinatario' => $token->email,
            'bcc' => $sessao->email, // manda com cópia para o email da sessão
            'assunto' => 'Credenciais de votação: ' . $sessao->nome . ' - ' . $sessao->quando,
            'corpo' => $corpo,
            'alt' => $corpo,
            'responder_para' => $sessao->email,
            'embedded' => [
                ['nome' => 'qrcode.png', 'data' => base64_encode($qrcode)],
                ['nome' => 'headtop.png', 'data' => base64_encode(file_get_contents(TPL . '/email/headtop.png'))],
            ],
        ]);
        if ($now) {
            exec('php ' . ROOTDIR . '/cli/processarEmailsFila.php > /dev/null &');
        }
        return $ret;
    }

    public static function sendControle($sessao)
    {
        $sessao->wwwroot = getenv('WWWROOT');
        $lista = $sessao->sharedUsuarioList;
        foreach ($lista as $dest) {
            $tpl = new Template(TPL . '/email/controle.html');
            $tpl->S = $sessao;
            $tpl->TA = array_pop($sessao->withCondition("tipo = 'apoio'")->ownTokenList);
            $tpl->TP = array_pop($sessao->withCondition("tipo = 'painel'")->ownTokenList);
            $tpl->TR = array_pop($sessao->withCondition("tipo = 'recepcao'")->ownTokenList);
            $tpl->dest = $dest;
            $corpo = $tpl->parse();

            SELF::adicionarFila($sessao, [
                'destinatario' => $dest->email,
                'assunto' => 'Credenciais de controle: ' . $sessao->nome . ' - ' . $sessao->quando,
                'corpo' => $corpo,
                'alt' => $corpo,
                'responder_para' => $sessao->email,
                'embedded' => [
                    ['nome' => 'headtop.png', 'data' => base64_encode(file_get_contents(TPL . '/email/headtop.png'))],
                ],
            ]);
        }
        exec('php ' . ROOTDIR . '/cli/processarEmailsFila.php > /dev/null &');
        return true;
    }

    public static function sendNovoGerente($sessao, $gerente)
    {
    }

    public static function sendExportarVotacao($export)
    {
        $votacao = json_decode(json_encode($export));
        $tpl = new Template(TPL . '/email/exportarVotacao.html');
        $sessao = $votacao->sessao;
        $sessao->link_ori = getenv('WWWROOT') . '/gerente/' . $sessao->id;
        $tpl->S = $sessao;
        $tpl->now = date('d/m/Y H:i:s');

        // vamos calcular a duração da votação
        $diff = strtotime($votacao->data_fim) - strtotime($votacao->data_ini);
        if ($diff >= 60) {
            $m = abs(floor($diff / 60));
            $s = $diff - ($m * 60);
            $str = $m . 'm:' . $s . 's';
        } else {
            $str = $diff . 's';
        }
        $votacao->duracao = $str;

        $tpl->V = $votacao;

        // vamos mostrar as alternativas e resumo
        foreach ($votacao->ownAlternativa as $a) {
            $tpl->A = $a;
            $tpl->block('block_alternativa');
        }

        // vamos mostrar as respostas individuais
        $i = 1;
        $alt = '';
        $respostas = $votacao->ownResposta;
        foreach ($respostas as $r) {
            // vamos juntar os votos computados da mesma pessoa e realçar a alternativa final
            $alt .= ($r->last == 1) ? "*$r->alternativa*:" : "$r->alternativa:";
            $next = next($respostas);
            if ($votacao->tipo == 'aberta') {
                if (!isset($next->apelido) || ($next->apelido != $r->apelido)) {
                    $r->alternativa = substr($alt, 0, -1);
                    $r->i = $i;
                    $tpl->R = $r;
                    $tpl->block('block_resposta_aberta');
                    $alt = '';
                    $i++;
                }
            } else {
                if (!isset($next->token) || ($next->token != $r->token)) {
                    $r->alternativa = substr($alt, 0, -1);
                    $r->i = $i;
                    $tpl->R = $r;
                    $tpl->block('block_resposta_secreta');
                    $alt = '';
                    $i++;
                }
            }
        }

        // vamos mostrar os eleitores habilitados
        if ($votacao->tipo == 'fechada') {
            $i = 1;
            foreach ($votacao->eleitor_fechado as $e) {
                $e->i = $i;
                $tpl->E = $e;
                $tpl->block('block_eleitor_fechado');
                $i++;
            }
        }
        $corpo = $tpl->parse();
        SELF::adicionarFila($sessao, [
            'destinatario' => $sessao->email,
            'assunto' => 'Relatório de votação: ' . mb_substr($votacao->nome, 0, 30) . '.. - ' . $sessao->nome,
            'corpo' => $corpo,
            'alt' => $corpo,
            'responder_para' => $sessao->email,
            'embedded' => [
                ['nome' => 'headtop.png', 'data' => base64_encode(file_get_contents(TPL . '/email/headtop.png'))],
            ],
        ]);
        exec('php ' . ROOTDIR . '/cli/processarEmailsFila.php > /dev/null &');
        return true;
    }

    public static function adicionarFila($sessao, $arr)
    {
        $email = R::dispense('email');
        $email->import(array_map('serialize', $arr));
        $email->enviado = null;
        $email->sessao_id = $sessao->id;
        $id = R::store($email);
        return true;
    }

    public static function processarFila()
    {
        return R::transaction(function () {
            $emails = R::findForUpdate('email', 'enviado IS NULL');
            $count = 0;
            foreach ($emails as $email) {
                $email_arr = $email->export();
                unset($email_arr['id']);
                unset($email_arr['sessao_id']);

                $arr = array_map('unserialize', $email_arr);
                $envio = SELF::send($arr);
                if ($envio == true) {
                    $email->enviado = date('Y-m-d H:i:s');
                    $context = [
                        'sessao_id' => $email->sessao_id,
                        'assunto' => $arr['assunto'],
                    ];
                    Log::email('enviado - ' . $arr['destinatario'], $context);
                    R::store($email);
                } else {
                    $context = [
                        'sessao_id' => $email->sessao_id,
                        'assunto' => $arr['assunto'],
                        'erro' => $envio,
                    ];
                    Log::email('erro - ' . $arr['destinatario'], $context);
                }

                $count++;
            }
            return $count;
        });
    }

    public static function send($arr)
    {
        $mail = new PHPMailer();
        //$mail->CharSet = 'UTF-8';
        //$mail->Encoding = 'base64';
        $mail->IsSMTP();

        $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
        //$mail->SMTPDebug = SMTP::DEBUG_CONNECTION;

        //$mail->SMTPKeepAlive = true;
        $mail->Host = getenv('EMAIL_HOST');
        $mail->Port = getenv('EMAIL_PORT');
        //$mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true;


        if (getenv('EMAIL_AUTH_TYPE') == 'oauth') {
            $mail->AuthType = 'XOAUTH2';

            $email = getenv('EMAIL');
            $clientId = getenv('EMAIL_OAUTH_CLIENT_ID');
            $clientSecret = getenv('EMAIL_OAUTH_CLIENT_SECRET');
            $refreshToken = getenv('EMAIL_OAUTH_REFRESH_TOKEN');

            $provider = new Google([
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ]);

            $mail->setOAuth(new OAuth([
                'provider' => $provider,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'refreshToken' => $refreshToken,
                'userName' => $email,
            ]));
        } elseif (getenv('EMAIL_AUTH_TYPE') == 'login') {
            $mail->AuthType = 'LOGIN';
            $mail->SMTPAutoTLS=false;
            $mail->Username = getenv('EMAIL');
            $mail->Password = getenv('EMAIL_PWD');
        } elseif (getenv('EMAIL_AUTH_TYPE') == 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Username = getenv('EMAIL');
            $mail->Password = getenv('EMAIL_PWD');
        }

        $mail->setLanguage('pt_br');

        $mail->setFrom(getenv('EMAIL'), utf8_decode("Votação rápida"));
        $mail->AddAddress(utf8_decode($arr['destinatario']));

        (isset($arr['bcc'])) ? $mail->addBCC($arr['bcc']) : '';

        !empty($arr['responder_para']) ? $mail->addReplyTo($arr['responder_para']) : '';

        if (!empty($arr['embedded'])) {
            foreach ($arr['embedded'] as $embedded) {
                $mail->addStringEmbeddedImage(base64_decode($embedded['data']), $embedded['nome'], $embedded['nome']);
            }
        }

        //$mail->WordWrap = 50; // Definir quebra de linha
        $mail->IsHTML = true; // Enviar como HTML
        $mail->Subject = utf8_decode($arr['assunto']);
        //$mail->Body = $arr['corpo']; 
        $mail->msgHTML($arr['corpo']);
        //$mail->AltBody = $arr['alt']; //PlainText, para caso quem receber o email não aceite o corpo HTML
        $mail->Debugoutput = function ($str, $level) use ($arr) {
            //echo "debug level $level; message: $str";
            $arq = LOCAL . '/emaillog.txt';
            file_put_contents($arq, $str, FILE_APPEND);
            // $context = [
            //     'debug level' => $level,
            //     'message' => $str,
            //     'dest' => $arr['destinatario'],
            //     'assunto' => $arr['assunto'],
            // ];
            // Log::email('erro - ' . $arr['destinatario'], $context);
        };

        $sent = $mail->send();
        $mail->smtpClose();
        file_put_contents(LOCAL . '/emaillog3.txt', $mail->getSentMIMEMessage(), FILE_APPEND);
        file_put_contents(LOCAL . '/emaillog2.txt', $mail->ErrorInfo, FILE_APPEND);

        if (!$sent) {
            return $mail->ErrorInfo;
        } else {
            return true;
        }
    }

    protected static function qrCodePngData($url)
    {
        $barcodeobj = new \TCPDF2DBarcode($url, 'QRCODE,M');
        return $barcodeobj->getBarcodePngData(6, 6, array(0, 0, 0));
    }
}
