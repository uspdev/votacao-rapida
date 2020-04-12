<?php
require_once __DIR__ . '/../app/bootstrap.php';

use Uspdev\Votacao\View;

session_start();

// Rotas

Flight::route('/', function () {
    View::index();
});

Flight::route('GET /demo', function () {
    View::demo();
});

Flight::route('GET /apoio', function () {
    View::apoioGet();
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

Flight::route('GET /@hash', function ($hash) {
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
