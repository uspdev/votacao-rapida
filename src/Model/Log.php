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
