<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

use Uspdev\Votacao\Model\Email;

/**
 * Este script é chamado pelo sistema (ou via cron) para enviar e-mails pendentes na fila.
 * A fila reside na tabela `email` (onde `enviado IS NULL`).
 */
$total = 0;
do {
    $count = Email::processarFila(100);
    $total += $count;
} while ($count >= 100);

echo '[' . date('Y-m-d H:i:s') . '] Processados ' . $total . ' e-mail(s).' . PHP_EOL;
