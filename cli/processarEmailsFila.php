<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

use Uspdev\Votacao\Model\Email;
/**
 * Este script é chamado pelo sistema para enviar emails que estão em fila
 * A fila é na tabela email. Ao enviar, ele registra a data e hora do envio.
 */
$count = Email::processarFila();
echo 'Enviados ' . $count . ' emails.'.PHP_EOL;
