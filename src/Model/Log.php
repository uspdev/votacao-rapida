<?php

namespace Uspdev\Votacao\Model;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * Model Log
 *
 * Responsável pelo gerenciamento, registro estruturado em JSON e leitura dos logs
 * da aplicação através do Monolog com rotação diária de arquivos.
 */
class Log
{
    /**
     * Instância do Logger do Monolog.
     *
     * @var \Monolog\Logger|null
     */
    private static $logger;

    /**
     * Registra mensagem no canal de logs de e-mail.
     *
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados contextuais adicionais
     * @return void
     */
    public static function email($message, $context = [])
    {
        $log = self::getLogger('email');
        $log->info($message, $context);
    }

    /**
     * Registra mensagem no canal de logs de autenticação.
     *
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados contextuais adicionais
     * @return void
     */
    public static function auth($message, $context = [])
    {
        $log = self::getLogger('auth');
        $log->info($message, $context);
    }

    /**
     * Registra mensagem no canal de logs de sessões.
     *
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados contextuais adicionais
     * @return void
     */
    public static function sessao($message, $context = [])
    {
        $log = self::getLogger('sessao');
        $log->info($message, $context);
    }

    /**
     * Registra mensagem no canal de logs de votações.
     *
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados contextuais adicionais
     * @return void
     */
    public static function votacao($message, $context = [])
    {
        $log = self::getLogger('votacao');
        $log->info($message, $context);
    }

    /**
     * Registra mensagem no canal de logs de banco de dados.
     *
     * @param string $message Mensagem a ser registrada
     * @param array $context Dados contextuais adicionais
     * @return void
     */
    public static function db($message, $context = [])
    {
        $log = self::getLogger('db');
        $log->info($message, $context);
    }

    /**
     * Lista as linhas de log dos arquivos rotativos mais recentes do Monolog.
     *
     * @param int $numLogFiles Quantidade de arquivos de log mais recentes a serem lidos (padrão: 5)
     * @return array<int, string> Lista de linhas de log em formato JSON
     */
    public static function listar($numLogFiles = 5)
    {
        $files = glob(LOCAL . '/log/info*.log');
        arsort($files, SORT_STRING);
        $i = 0;
        $logs = [];
        foreach ($files as $file) {
            $logs = array_merge($logs, file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES));
            if ($i < $numLogFiles) {
                $i++;
            } else {
                break 1;
            }
        }
        return $logs;
    }

    /**
     * Obtém ou inicializa a instância do Logger com RotatingFileHandler em JSON.
     *
     * @param string $channel_name Nome do canal de log
     * @return \Monolog\Logger Instância configurada do Monolog Logger
     */
    private static function getLogger($channel_name)
    {
        if (!isset(self::$logger)) {
            self::$logger = new Logger($channel_name);
            $stream = new RotatingFileHandler(LOCAL . '/log/info.log', Logger::INFO);
            $stream->setFormatter(new JsonFormatter());
            self::$logger->pushHandler($stream);
        }
        return self::$logger;
    }
}
