<?php

namespace Uspdev\Votacao;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use raelgc\view\Template;
use Uspdev\Votacao\Controller\Gerente;

class Email
{
    public static function sendVotacao($sessao, $token)
    {
        $sessao->link_direto = getenv('WWWROOT') . '/' . $sessao->hash . '/' . $token->token;
        $qrcode = SELF::qrCodePngData($sessao->link_direto);
        $tpl = new Template(TPL . '/email/votacao.html');
        $tpl->S = $sessao;
        $tpl->T = $token;
        //$tpl->qrcode = base64_encode($qrcode);
        $corpo = $tpl->parse();

        return SELF::send([
            'destinatario' => $token->email,
            'assunto' => 'Credenciais de votação: ' . $sessao->nome . ' - data hora - ' . generateRandomString(4),
            'corpo' => $corpo,
            'alt' => $corpo,
            'responderPara' => $sessao->email,
            'embedded' => [
                ['nome' => 'qrcode.png', 'data' => $qrcode],
                ['nome' => 'headtop.png', 'data' => file_get_contents(TPL . '/email/headtop.png')],
            ],
        ]);
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
        $mail->Subject = utf8_decode($arr['assunto']); // Assunto
        $mail->Body = $arr['corpo']; //Corpo da mensagem caso seja HTML
        $mail->AltBody = $arr['alt']; //PlainText, para caso quem receber o email não aceite o corpo HTML
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
