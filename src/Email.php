<?php

namespace Uspdev\Votacao;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use raelgc\view\Template;
use Uspdev\Votacao\Controller\Token;

class Email
{
    public static function sendVotacao($sessao, $token)
    {
        $sessao->link_direto = getenv('WWWROOT') . '/' . $sessao->hash . '/' . $token->token;
        $qrcode = SELF::qrCodePngData($sessao->link_direto);
        $tpl = new Template(TPL . '/email/votacao.html');
        $tpl->S = $sessao;
        $tpl->T = $token;
        $corpo = $tpl->parse();

        return SELF::send([
            'destinatario' => $token->email,
            'assunto' => 'Credenciais de votação: ' . $sessao->nome . ' - ' . $sessao->quando . ' - ',
            'corpo' => $corpo,
            'alt' => $corpo,
            'responderPara' => $sessao->email,
            'embedded' => [
                ['nome' => 'qrcode.png', 'data' => $qrcode],
                ['nome' => 'headtop.png', 'data' => file_get_contents(TPL . '/email/headtop.png')],
            ],
        ]);
    }

    public static function sendControle($sessao)
    {
        $sessao->wwwroot = getenv('WWWROOT');
        $lista = $sessao->sharedUsuarioList;
        foreach ($lista as $dest) {
            //$sessao->link_direto = getenv('WWWROOT') . '/' . $sessao->hash . '/' . $token->token;
            //$qrcode = SELF::qrCodePngData($sessao->link_direto);
            $tpl = new Template(TPL . '/email/controle.html');
            $tpl->S = $sessao;
            $tpl->TA = Token::ObterToken($sessao, 'apoio');
            $tpl->TP = Token::ObterToken($sessao, 'painel');
            $tpl->TR = Token::ObterToken($sessao, 'recepcao');
            $tpl->dest = $dest;
            $corpo = $tpl->parse();

            SELF::send([
                'destinatario' => $dest->email,
                'assunto' => 'Credenciais de controle: ' . $sessao->nome . ' - ' . $sessao->quando,
                'corpo' => $corpo,
                'alt' => $corpo,
                'responderPara' => $sessao->email,
                'embedded' => [
                    // ['nome' => 'qrcode.png', 'data' => $qrcode],
                    ['nome' => 'headtop.png', 'data' => file_get_contents(TPL . '/email/headtop.png')],
                ],
            ]);
        }
        return true;
    }

    public static function send($arr)
    {
        $mail = new PHPMailer();
        //$mail->CharSet = 'UTF-8';
        //$mail->Encoding = 'base64';

        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->IsSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL');
        $mail->Password = getenv('EMAIL_PWD');
        $mail->Port = 587;

        $mail->setLanguage('pt_br');

        $mail->setFrom(getenv('EMAIL'), utf8_decode("Votação rápida"));
        $mail->AddAddress($arr['destinatario']);

        !empty($arr['responderPara']) ? $mail->addReplyTo($arr['responderPara']) : '';

        if (!empty($arr['embedded'])) {
            foreach ($arr['embedded'] as $embedded) {
                $mail->addStringEmbeddedImage($embedded['data'], $embedded['nome'], $embedded['nome']);
            }
        }

        //$mail->WordWrap = 50; // Definir quebra de linha
        $mail->IsHTML = true; // Enviar como HTML
        $mail->Subject = utf8_decode($arr['assunto']);
        //$mail->Body = $arr['corpo']; 
        $mail->msgHTML($arr['corpo']);
        //$mail->AltBody = $arr['alt']; //PlainText, para caso quem receber o email não aceite o corpo HTML
        $mail->send();
        return true;
        if (!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return true;
        };
    }

    protected static function qrCodePngData($url)
    {
        $barcodeobj = new \TCPDF2DBarcode($url, 'QRCODE,M');
        return $barcodeobj->getBarcodePngData(6, 6, array(0, 0, 0));
    }
}
