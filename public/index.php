<?php

namespace Uspdev\Votacao\View;

require_once __DIR__ . '/../app/bootstrap.php';

use \Flight;

session_start();

// Rotas

Flight::route('/', function () {
    Run::index();
});

Flight::route('GET /login', function () {
    Run::login();
});

Flight::route('GET /logout', function () {
    Run::logout();
});

Flight::route('GET /demo', function () {
    Demo::demo();
});

Flight::route('/sessao/@id', function ($id) {
    Gerente::sessao($id);
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

Flight::route('POST /@hash', function ($hash) {
    $data = Flight::request()->data;
    Run::hashPost($hash, $data);
});

Flight::route('GET /@hash/@token', function ($hash, $token) {
    Run::hashToken($hash, $token);
});

Flight::start();
