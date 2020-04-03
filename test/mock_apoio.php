<?php
require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::useFeatureSet('latest');

//R::wipe('resposta');

$base = 'http://localhost/git/uspdev/votacao/public/run';

$token = obterTokenApoio('hash001');

$sessao = obterSessao('hash001',$token);
print_r($sessao);

echo $token, PHP_EOL;

