<?php
require_once __DIR__ . '/../app/bootstrap.php';

use Uspdev\Votacao\View;

session_start();

// Rotas

Flight::route('/', function () {
    View::index();
});

Flight::route('GET /login', function () {
    View::login();
});

Flight::route('GET /logout', function () {
    View::logout();
});

Flight::route('GET /demo', function () {
    View::demo();
});

Flight::route('GET /apoio', function () {
    View::apoioGet();
});

Flight::route('POST /apoio', function () {
    $data = Flight::request()->data;
    View::apoioPOST($data);
});

Flight::route('GET /painel', function () {
    View::painelGet();
});

Flight::route('GET /votacao', function () {
    View::votacaoGet();
});

Flight::route('POST /votacao', function () {
    $data = Flight::request()->data;
    View::votacaoPOST($data);
});

Flight::route('GET /@hash:[A-Z]{20}', function ($hash) {
    View::hashGet($hash);
});

Flight::route('POST /@hash', function ($hash) {
    $data = Flight::request()->data;
    View::hashPost($hash, $data);
});

Flight::route('GET /@hash/@token', function ($hash, $token) {
    View::hashToken($hash, $token);
});

Flight::start();
