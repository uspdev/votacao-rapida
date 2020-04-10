<?php
require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::useFeatureSet('latest');

$hash = 'hash001';

$sessao = R::findOne('sessao', 'hash = ?', [$hash]);

$logo2 = __DIR__.'/../template/logo_eesc_horizontal.png';

$filename = gerarListaQrcodePdf($sessao, $logo2);

$sessao->arq_tokens_pdf = $filename;
R::store($sessao);

echo $filename , ' gerado com sucesso', PHP_EOL;