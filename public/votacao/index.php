<?php
require_once __DIR__ . '/../../app/bootstrap.php';

session_start();

if (isset($_GET['hash'])) {
    $_SESSION['votacao']['hash'] = $_GET['hash'];
    $_SESSION['votacao']['token'] = $_GET['token'];
    header('Location:' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_SESSION['votacao']['hash'])) {
    $hash = $_SESSION['votacao']['hash'];
    $token = $_SESSION['votacao']['token'];
} else {
    header('Location:../');
    exit;
}

use raelgc\view\Template;

$sessao = obterSessao($hash, $token);
if (!is_object($sessao)) {
    echo 'Mensagem ao tentar obter sessão: ', $sessao;exit;
}

//print_r($sessao); //exit;

$sessao->token = $token;


// post de formulario
if (isset($_POST['acao'])) {

    $data = $_POST;
    $res = post($hash, $sessao->token, $data);
    print_r($res);
    header('Location:' . $_SERVER['PHP_SELF']);
    exit;
}

$tpl = new Template(__DIR__ . '/../../template/votacao_index.html');
$tpl->S = $sessao;
if (!empty($sessao->msg)) {
    $tpl->msg = $sessao->msg;
    $tpl->block('block_msg');

} else {
    //print_r($sessao->em_tela); //exit;
    $tpl->V = $sessao->render_form;
    if (!empty($sessao->render_form->alternativas)) {
        foreach ($sessao->render_form->alternativas as $a) {
            $tpl->A = $a;
            $tpl->block('block_alternativa');
        }
    }
    $tpl->block('block_form');
}

$tpl->show();
