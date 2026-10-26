<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

use Uspdev\Votacao\Model\Email;

/**
 * Este script é chamado pelo sistema (ou via cron) para enviar e-mails pendentes na fila.
 * A fila reside na tabela `email` (onde `enviado IS NULL`).
 *
 * Utiliza trava exclusiva não-bloqueante (flock) para evitar concorrência entre
 * múltiplos cliques de usuários e/ou execuções agendadas de cron.
 */
$lockDir = LOCAL . '/log';
if (!is_dir($lockDir)) {
    mkdir($lockDir, 0775, true);
}
$lockFile = $lockDir . '/email_queue.lock';
$fp = fopen($lockFile, 'c+');

if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
    // Já existe um worker ativo rodando neste instante. Encerra silenciosamente.
    exit(0);
}

$total = 0;
do {
    $count = Email::processarFila(100);
    $total += $count;
} while ($count >= 100);

flock($fp, LOCK_UN);
fclose($fp);

if ($total > 0) {
    echo '[' . date('Y-m-d H:i:s') . '] Processados ' . $total . ' e-mail(s).' . PHP_EOL;
}
