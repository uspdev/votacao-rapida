<?php
require_once __DIR__.'/../app/bootstrap.php';
use Uspdev\Webservice\Auth;
Auth::salvarUsuario(['username'=>'admin', 'pwd'=>'admin', 'admin'=>'1', 'allow'=>'']);