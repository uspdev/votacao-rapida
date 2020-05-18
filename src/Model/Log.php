<?php

namespace Uspdev\Votacao\Model;

use \Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

class Log
{
    private static $logger;

    public static function auth($message, $data = [])
    {
        $log = SELF::getLogger('auth');
        $log->info($message, $data);
    }

    public static function listar($numLogFiles = 5) {
        $files = glob(LOCAL. '/log/*.log');
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
