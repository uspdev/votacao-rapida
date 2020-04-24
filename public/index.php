<?php

namespace Uspdev\Votacao\View;

use Uspdev\Votacao\SessaoPhp as SS;
use Uspdev\Votacao\Factory;

require_once __DIR__ . '/../app/bootstrap.php';

use \Flight;

SS::start();

// Rotas

Flight::route('/', function () {
    Gerente::index();
});

Flight::route('/session', function () {
    echo '<pre>';
    print_r($_SESSION);
});

Flight::route('GET /login', function () {
    $gerente = Factory::gerente(Flight::request());
    $gerente->login();
});

Flight::route('GET /logout', function () {
    Gerente::logout();
});

Flight::route('GET /demo', function () {
    Demo::demo();
});

Flight::route('GET /gerente/@id', function ($id) {
    Gerente::sessao($id);
});

Flight::route('POST /gerente/@id', function ($id) {
    $gerente = Factory::gerente(Flight::request());
    $gerente->sessaoPost($id);
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

Flight::route('POST /votacao', function () {
    $data = Flight::request()->data;
    Run::votacaoPOST($data);
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

Flight::start();
