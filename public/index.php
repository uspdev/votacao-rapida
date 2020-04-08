<?php
require_once __DIR__ . '/../app/bootstrap.php';

$hash = 'hash001';

if (!empty($_POST['token'])) {
    $token = $_POST['token'];
    $sessao = obterSessao($hash, $token);

    switch ($sessao->token->tipo) {
        case 'apoio':
            header('Location: apoio/?hash=' . $hash . '&token=' . $token);
            break;
        case 'tela':
            header('Location: tela/?hash=' . $hash . '&token=' . $token);
            break;
        case 'votacao':
            header('Location: votacao/?hash=' . $hash . '&token=' . $token);
            break;
    }
    exit;
}

use raelgc\view\Template;
use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::useFeatureSet('latest');

$sessao = obterSessao($hash, '');

//print_r($sessao);

$tpl = new Template(__DIR__ . '/../template/index.html');
$tpl->S = $sessao;
$tpl->show();
