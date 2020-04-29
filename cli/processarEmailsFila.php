<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/funcoes_cli.php';

use Uspdev\Votacao\Email;

$count = Email::processarFila();

echo 'Enviados ' . $count . ' emails.'.PHP_EOL;
