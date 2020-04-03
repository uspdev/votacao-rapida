<?php
require_once __DIR__ . '/../../app/bootstrap.php';

session_start();

if (isset($_GET['hash'])) {
    $_SESSION['tela']['hash'] = $_GET['hash'];
    $_SESSION['tela']['token'] = $_GET['token'];
    header('Location:' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_SESSION['tela']['hash'])) {
    $hash = $_SESSION['tela']['hash'];
    $token = $_SESSION['tela']['token'];
} else {
    header('Location:../');
    exit;
}

use raelgc\view\Template;

//$base = getenv('DOMINIO') . '/../run';
$p = parse_url(getenv('DOMINIO'));
$base = $p['scheme'] . '://' . $p['host'] . str_replace('tela', 'api/run', $p['path']);

// vamos obter a sessao pelo webservice
$sessao = obterSessao($hash, $token);
if (!is_object($sessao)) {
    echo 'Mensagem ao tentar obter sessão: ', $sessao;exit;
}

//print_r($sessao); //exit;

$sessao->token = $token;

$tpl = new Template(__DIR__ . '/../../template/tela_index.html');
$tpl->S = $sessao;
if (!empty($sessao->msg)) {
    $tpl->msg = $sessao->msg;
    $tpl->block('block_msg');

} else {
    //print_r($sessao->em_tela); //exit;
    $tpl->V = $sessao->em_tela;
    if (!empty($sessao->em_tela->alternativas)) {
        foreach ($sessao->em_tela->alternativas as $a) {
            $tpl->alternativa = $a->texto;
            $tpl->block('block_alternativa');
        }
    }
    if (!empty($sessao->em_tela->respostas)) {
        foreach ($sessao->em_tela->respostas as $r) {
            $tpl->R = $r;
            $tpl->block('block_resposta');
        }
    }
    if ($sessao->em_tela->estado == 'Em votação') {
        $tpl->block('block_computados');
    }

    $tpl->block('block_votacao');
}

$tpl->show();
