<?php
require_once __DIR__ . '/../app/bootstrap.php';

use raelgc\view\Template;
use \RedBeanPHP\R as R;
use Uspdev\Votacao\Form;
use Uspdev\Votacao\View;

//$hash = 'hash001';

session_start();

Flight::route('/', function () {
    echo 'hello';
});

Flight::route('/apoio', function () {
    View::apoioGet();
});

Flight::route('/painel', function () {
    View::painelGet();
});

Flight::route('/votacao', function () {
    View::votacaoGet();
});

Flight::route('GET /@hash', function ($hash) {
    View::hashGet($hash);
});

Flight::route('POST /@hash', function ($hash) {
    $data = Flight::request()->data;
    View::hashPost($hash, $data);
});

Flight::route('/@hash/@token', function ($hash, $token) {
    View::hashToken($hash, $token);
});

Flight::start();
