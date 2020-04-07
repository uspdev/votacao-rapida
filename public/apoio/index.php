<?php
require_once __DIR__ . '/../../app/bootstrap.php';

session_start();

if (isset($_GET['hash'])) {
    $_SESSION['apoio']['hash'] = $_GET['hash'];
    $_SESSION['apoio']['token'] = $_GET['token'];
    header('Location:' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_SESSION['apoio']['hash'])) {
    $hash = $_SESSION['apoio']['hash'];
    $token = $_SESSION['apoio']['token'];
} else {
    header('Location:../');
    exit;
}

use raelgc\view\Template;

// vamos obter a sessao pelo webservice
$sessao = obterSessao($hash, $token);
if (!is_object($sessao)) {
    echo 'Mensagem ao tentar obter sessão: ', $sessao;exit;
}
$sessao->token = $token;

//print_r($sessao);

// acoes para a rota de apoio
if (isset($_GET['acao'])) {
    $data = ['acao' => $_GET['acao'], 'votacao_id' => $_GET['votacao_id']];
    $res = post($hash, $sessao->token, $data);
    header('Location:' . $_SERVER['PHP_SELF']);
    exit;
}

$tpl = new Template(__DIR__ . '/../../template/apoio_index.html');
$tpl->S = $sessao;
foreach ($sessao->votacoes as $v) {
    //print_r($v);exit;
    $tpl->V = $v;
    foreach ($v->acoes as $acao) {
        $tpl->cod = $acao->cod;
        $tpl->acao = $acao->nome;
        $tpl->block('block_acao');
    }
    $tpl->block('block_votacao');
}

$tpl->show();
