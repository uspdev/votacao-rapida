<?php

namespace Uspdev\Votacao\Model;

use League\OAuth2\Client\Provider\Google;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use raelgc\view\Template;
use \RedBeanPHP\OODBBean;
use \RedBeanPHP\R as R;

/**
 * Model Email
 *
 * Responsável pelo gerenciamento, geração de templates, enfileiramento
 * e disparo de e-mails da aplicação (credenciais de votação, controle e relatórios).
 */
class Email
{
    /**
     * Envia e-mail com as credenciais de votação para todos os tokens de votação aberta da sessão.
     *
     * @param OODBBean $sessao Objeto/Bean da sessão de votação
     * @return array{0: int, 1: int} Retorna array contendo [sucessos, erros]
     */
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
        self::dispararProcessamentoFila();
        return [$count, $erro];
    }

    /**
     * Gera o template e enfileira o e-mail de votação para um eleitor específico.
     *
     * @param OODBBean $sessao Objeto/Bean da sessão de votação
     * @param OODBBean $token Objeto/Bean do token associado ao eleitor
     * @param bool $now Se true, aciona imediatamente o script CLI de envio da fila em background
     * @return bool Retorna true se o e-mail foi adicionado à fila com sucesso
     */
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
            self::dispararProcessamentoFila();
        }
        return $ret;
    }

    /**
     * Enfileira as credenciais de controle da sessão (apoio, painel, recepção) para os gerentes/usuários.
     *
     * @param OODBBean $sessao Objeto/Bean da sessão de votação
     * @return bool Retorna true após enfileirar as mensagens
     */
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
        self::dispararProcessamentoFila();
        return true;
    }

    /**
     * Notifica a adição de um novo gerente à sessão (placeholder para futura implementação).
     *
     * @param OODBBean $sessao
     * @param mixed $gerente
     * @return void
     */
    public static function sendNovoGerente($sessao, $gerente)
    {
    }

    /**
     * Gera e envia o relatório com o resultado consolidado e votos individuais de uma votação finalizada.
     *
     * @param object|array $export Dados consolidados da votação para exportação
     * @return bool Retorna true se adicionado à fila com sucesso
     */
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
        self::dispararProcessamentoFila();
        return true;
    }

    /**
     * Salva uma mensagem na tabela de fila de e-mails do banco de dados.
     *
     * @param OODBBean $sessao Objeto/Bean da sessão vinculada
     * @param array $arr Dados do e-mail (destinatario, assunto, corpo, embedded, etc.)
     * @return bool Retorna true após persistir
     */
    public static function adicionarFila($sessao, $arr)
    {
        $email = R::dispense('email');
        $email->import(array_map('serialize', $arr));
        $email->enviado = null;
        $email->sessao_id = $sessao->id;
        $id = R::store($email);
        return true;
    }

    /**
     * Processa as mensagens pendentes na fila de e-mails (`enviado IS NULL`).
     *
     * Dispara as mensagens via SMTP ou salva em log de acordo com a configuração,
     * atualizando a data de envio (marcando com '(log)' quando em simulação) e gerando registros de log.
     *
     * @param int $limit Quantidade máxima de e-mails a processar por lote
     * @return int Retorna a quantidade de e-mails processados
     */
    public static function processarFila($limit = 100)
    {
        R::selectDatabase('votacao');
        $emails = R::find('email', 'enviado IS NULL ORDER BY id ASC LIMIT ?', [$limit]);
        $count = 0;
        $isLog = !self::isConfigured();
        foreach ($emails as $email) {
            $email_arr = $email->export();
            unset($email_arr['id']);
            unset($email_arr['sessao_id']);
            unset($email_arr['enviado']);

            $arr = array_map(function ($v) {
                if (is_string($v)) {
                    $u = @unserialize($v);
                    return ($u !== false || $v === 'b:0;') ? $u : $v;
                }
                return $v;
            }, $email_arr);

            $envio = self::send($arr);
            if ($envio === true) {
                $email->enviado = $isLog ? (date('Y-m-d H:i:s') . ' (log)') : date('Y-m-d H:i:s');
                $context = [
                    'sessao_id' => $email->sessao_id,
                    'assunto' => $arr['assunto'] ?? '',
                ];
                $logMsg = $isLog ? ('salvo em log - ' . ($arr['destinatario'] ?? '')) : ('enviado - ' . ($arr['destinatario'] ?? ''));
                Log::email($logMsg, $context);
                R::store($email);
            } else {
                $context = [
                    'sessao_id' => $email->sessao_id,
                    'assunto' => $arr['assunto'] ?? '',
                    'erro' => $envio,
                ];
                Log::email('erro - ' . ($arr['destinatario'] ?? ''), $context);
            }

            $count++;
        }
        return $count;
    }

    /**
     * Dispara o processamento da fila de e-mails.
     *
     * Em modo log, processa imediatamente no próprio processo sem overhead de subprocesso.
     * Em modo SMTP, dispara script CLI em background utilizando o binário PHP do sistema.
     *
     * @return bool|int Retorna quantidade de e-mails processados em modo síncrono ou true em background
     */
    public static function dispararProcessamentoFila()
    {
        if (!self::isConfigured()) {
            return self::processarFila();
        }

        $phpBin = defined('PHP_BINARY') && file_exists(PHP_BINARY) ? PHP_BINARY : 'php';
        exec($phpBin . ' ' . escapeshellarg(ROOTDIR . '/cli/processarEmailsFila.php') . ' > /dev/null &');
        return true;
    }

    /**
     * Verifica se as variáveis de ambiente possuem um servidor de e-mail válido configurado.
     *
     * @return bool Retorna true se houver host e remetente válidos configurados, ou false para simulação em log
     */
    public static function isConfigured()
    {
        $authType = strtolower(getenv('EMAIL_AUTH_TYPE') ?: '');
        if ($authType === 'log') {
            return false;
        }

        $host = getenv('EMAIL_HOST');
        $email = getenv('EMAIL');

        if (empty($host) || empty($email) || strtolower($host) === 'log') {
            return false;
        }

        return true;
    }

    /**
     * Registra o e-mail em arquivo de log quando não há servidor de e-mail ativo.
     *
     * Grava os dados completos em `local/log/emails_YYYY-MM.log` e registra o evento no Monolog.
     *
     * @param array $arr Dados do e-mail (destinatario, assunto, corpo, bcc, responder_para)
     * @return bool Sempre retorna true indicando que o registro foi realizado
     */
    public static function logEmail($arr)
    {
        $dataHora = date('Y-m-d H:i:s');
        $destinatario = $arr['destinatario'] ?? '';
        $assunto = $arr['assunto'] ?? '';
        $corpo = $arr['corpo'] ?? '';
        $responderPara = $arr['responder_para'] ?? '';
        $bcc = $arr['bcc'] ?? '';

        $logTxt = "================================================================================" . PHP_EOL;
        $logTxt .= "DATA/HORA: " . $dataHora . PHP_EOL;
        $logTxt .= "PARA: " . $destinatario . PHP_EOL;
        if (!empty($bcc)) {
            $logTxt .= "BCC: " . $bcc . PHP_EOL;
        }
        if (!empty($responderPara)) {
            $logTxt .= "RESPONDER PARA: " . $responderPara . PHP_EOL;
        }
        $logTxt .= "ASSUNTO: " . $assunto . PHP_EOL;
        $logTxt .= "--------------------------------- MENSAGEM -------------------------------------" . PHP_EOL;
        $logTxt .= $corpo . PHP_EOL;
        $logTxt .= "================================================================================" . PHP_EOL . PHP_EOL;

        $logDir = LOCAL . '/log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/emails_' . date('Y-m') . '.log';
        file_put_contents($logFile, $logTxt, FILE_APPEND);

        Log::email('email salvo em log (sem servidor configurado) - ' . $destinatario, [
            'assunto' => $assunto,
            'destinatario' => $destinatario,
            'arquivo' => $logFile,
        ]);

        return true;
    }

    /**
     * Envia o e-mail utilizando PHPMailer ou o registra em log caso não haja servidor configurado.
     *
     * Suporta métodos de autenticação: ssl, tls, login e oauth (XOAUTH2 com Google).
     *
     * @param array $arr Dados do e-mail a enviar (destinatario, assunto, corpo, etc.)
     * @return bool|string Retorna true em caso de sucesso ou string com ErrorInfo do PHPMailer
     */
    public static function send($arr)
    {
        if (!SELF::isConfigured()) {
            return SELF::logEmail($arr);
        }

        $mail = new PHPMailer();
        //$mail->CharSet = 'UTF-8';
        //$mail->Encoding = 'base64';
        $mail->IsSMTP();

        //$mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;

        //$mail->SMTPKeepAlive = true;
        $mail->Host = getenv('EMAIL_HOST');
        $mail->Port = getenv('EMAIL_PORT');
        $mail->SMTPAuth = true;

        switch (getenv('EMAIL_AUTH_TYPE')) {
            case 'oauth':
                $mail->AuthType = 'XOAUTH2';
                $email = getenv('EMAIL');
                $clientId = getenv('EMAIL_OAUTH_CLIENT_ID');
                $clientSecret = getenv('EMAIL_OAUTH_CLIENT_SECRET');
                $refreshToken = getenv('EMAIL_OAUTH_REFRESH_TOKEN');

                $provider = new Google(['clientId' => $clientId, 'clientSecret' => $clientSecret,]);
                $mail->setOAuth(new OAuth([
                    'provider' => $provider,
                    'clientId' => $clientId,
                    'clientSecret' => $clientSecret,
                    'refreshToken' => $refreshToken,
                    'userName' => $email,
                ]));
                break;
            case 'login':
                $mail->AuthType = 'LOGIN';
                $mail->SMTPAutoTLS = false;
                $mail->Username = getenv('EMAIL');
                $mail->Password = getenv('EMAIL_PWD');
                break;
            case 'tls':
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Username = getenv('EMAIL');
                $mail->Password = getenv('EMAIL_PWD');
                break;
            case 'ssl':
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Username = getenv('EMAIL');
                $mail->Password = getenv('EMAIL_PWD');
                break;
        }

        $mail->setLanguage('pt_br');
        $mail->setFrom(getenv('EMAIL'), utf8_decode("Votação rápida"));
        $mail->AddAddress($arr['destinatario']);

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
            $arq = LOCAL . '/log/emaillog-'.date('Y-m-d').'.txt';
            file_put_contents($arq, $str, FILE_APPEND);
        };

        $sent = $mail->send();
        $mail->smtpClose();

        if (!$sent) {
            return $mail->ErrorInfo;
        } else {
            return true;
        }
    }

    /**
     * Gera os dados binários da imagem PNG do QR Code correspondente à URL informada.
     *
     * @param string $url URL a ser embutida no QR Code
     * @return string Dados binários do arquivo PNG do QR Code
     */
    protected static function qrCodePngData($url)
    {
        $barcodeobj = new \TCPDF2DBarcode($url, 'QRCODE,M');
        return $barcodeobj->getBarcodePngData(6, 6, array(0, 0, 0));
    }
}
