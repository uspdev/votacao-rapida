<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Uspdev\Votacao\View\Api;

class apiLoginTest extends TestCase
{
    // testes de logins
    public function testNologin(): void
    {
        $endpoint = '/gerente/noLogin';
        $msg = Api::send($endpoint);

        $expected = '{"status":"erro","data":"Faltando dados do usuario"}';
        $this->assertEquals($expected, json_encode($msg, JSON_UNESCAPED_UNICODE));
    }

    public function testNologinComDados(): void
    {
        $endpoint = '/gerente/noLogin';
        $data = ['codpes' => '111'];
        $msg = Api::send($endpoint, $data);

        $expected = '{"status":"ok","data":"Log registrado com sucesso"}';
        $this->assertEquals($expected, json_encode($msg, JSON_UNESCAPED_UNICODE));
    }

    public function testLogin(): void
    {
        $endpoint = '/gerente/login';
        $msg = Api::send($endpoint);

        $expected = '{"status":"erro","data":"Faltando dados do usuario"}';
        $this->assertEquals($expected, json_encode($msg, JSON_UNESCAPED_UNICODE));
    }

    public function testLoginComDados(): void
    {
        $endpoint = '/gerente/login';
        $data = ['codpes' => '111'];
        $msg = Api::send($endpoint, $data);

        $expected = '"lastlogin":';
        $this->assertStringContainsString($expected, json_encode($msg, JSON_UNESCAPED_UNICODE));
    }
}
