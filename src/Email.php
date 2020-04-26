<?php

namespace Uspdev\Votacao;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Email
{
    public static function send($arr)
    {
        // arr tem = [destinatario, assunto, mensagem, anexo1, anexo2, responderPara]

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

        !empty($arr['anexo1']) ? $mail->AddEmbeddedImage($arr['anexo1'], 'anexo1', $arr['anexo1']) : '';    // Optional name

        //$mail->WordWrap = 50; // Definir quebra de linha
        $mail->IsHTML = true; // Enviar como HTML
        $mail->Subject = utf8_decode($arr['assunto']); // Assunto
        $mail->Body = '<br/>' . $arr['mensagem'] . '<br/>'; //Corpo da mensagem caso seja HTML
        $mail->AltBody = $arr['mensagem']; //PlainText, para caso quem receber o email não aceite o corpo HTML
        $mail->send();
        return true;
        if (!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return true;
        };
    }
}
