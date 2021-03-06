<?php

namespace Uspdev\Votacao\View;

use Uspdev\Votacao\View\SessaoPhp as SS;
use \Flight;

require_once __DIR__ . '/../app/bootstrap.php';

// inicializando session
SS::start();

// Rotas

Flight::route('/', function () {
    // devemos usar o factory para injetarmos _GET e _POST na classe
    $gerente = Factory::gerente(Flight::request());
    $gerente->index();
});

//somente para testes
Flight::route('/session', function () {
    if (SS::isAdmin()) {
        echo '<pre>';
        print_r($_SESSION);
    } else {
        Flight::notFound();
    }
});

Flight::route('GET /login(/@cod)', function ($cod) {
    $gerente = Factory::gerente(Flight::request());
    if (!$gerente->login($cod)) {
        Flight::notFound();
    }
});

Flight::route('GET /logout', function () {
    Gerente::logout();
});

Flight::route('GET /ajuda', function () {
    Ajuda::inicio();
});

Flight::route('GET|POST /aviso(/@id:[0-9]+)', function ($id) {
    $aviso = Factory::aviso(Flight::request());
    $aviso->mostrar($id);
});

Flight::route('GET /demo', function () {
    Demo::demo();
});

Flight::route('/gerente', function () {
    $gerente = Factory::gerente(Flight::request());
    $gerente->index();
});

Flight::route('/gerente/@id(/@aba)', function ($id, $aba) {
    $gerente = Factory::gerente(Flight::request());
    $gerente->sessao($id, $aba);
});

Flight::route('/admin(/@aba)', function ($aba) {
    if (SS::isAdmin()) {
        Admin::home($aba);
    } else {
        Flight::notFound();
    }
});

Flight::route('GET /apoio', function () {
    Run::apoioGet();
});

Flight::route('POST /apoio', function () {
    $data = Flight::request()->data;
    Run::apoioPOST($data);
});

Flight::route('GET /painel', function () {
    Run::painelGet();
});

Flight::route('GET /votacao', function () {
    Run::votacaoGet();
});

Flight::route('GET /recepcao', function () {
    Run::recepcao();
});

Flight::route('POST /votacao', function () {
    $data = Flight::request()->data;
    Run::votacaoPOST($data);
});

Flight::route('/ticket', function () {
    $run = Factory::run(Flight::request());
    $run->ticket();
});

Flight::route('/get_auth_token', function () {
    require_once(ROOTDIR.'/app/get_oauth_token.php');
});

Flight::route('GET /@hash:[A-Z]{20}', function ($hash) {
    Run::hashGet($hash);
});

Flight::route('POST /@hash:[A-Z]{20}', function ($hash) {
    $data = Flight::request()->data;
    Run::hashPost($hash, $data);
});

Flight::route('GET /@hash:[A-Z]{20}/@token:[A-Z]{6}', function ($hash, $token) {
    Run::hashToken($hash, $token);
});

Flight::route('GET /@hash:[A-Z]{20}/@ticket:[A-Z]{25}', function ($hash, $ticket) {
    Run::hashTicket($hash, $ticket);
});

Flight::start();
