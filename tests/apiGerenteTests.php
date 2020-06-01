<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Uspdev\Votacao\View\Api;

class apiGerenteTests extends TestCase
{
    public function testListarSessaoUsuarioInvalido(): void
    {
        $user = '1122'; //invalido
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $expected = '{"status":"erro","msg":"Usuário inválido"}';
        $this->assertEquals($expected, json_encode($sessoes, JSON_UNESCAPED_UNICODE));
    }

    public function testListarSessaoOk(): void
    {
        $user = '1575309'; //ok
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $this->assertGreaterThan(0, count((array) $sessoes));
    }

    public function testObterSessaoOk(): void
    {
        $user = '1575309'; //ok
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $id = $sessao->id;
        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user;
        $sessao = Api::send($endpoint);

        $expected = sprintf('"id":"%s"', $id);
        $this->assertStringContainsString($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testObterSessaoErro(): void
    {
        $user = '1575309'; // para listar sessoes
        $user2 = '111'; // para testar o acesso 
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $id = $sessao->id;
        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user2;
        $sessao = Api::send($endpoint);

        $expected = '{"status":"erro","msg":"Sessão inexistente ou sem acesso"}';
        $this->assertStringContainsString($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testNologin(): void
    {
        $endpoint = '/gerente/noLogin';
        $msg = Api::send($endpoint);

        $expected = '{"status":"erro","data":"Faltando dados do usuario"}';
        $this->assertEquals($expected, json_encode($msg, JSON_UNESCAPED_UNICODE));
    }

    public function testNologincomDados(): void
    {
        $endpoint = '/gerente/noLogin';
        $data = ['codpes'=>'111'];
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
        $data = ['codpes'=>'111'];
        $msg = Api::send($endpoint, $data);

        $expected = '"lastlogin":';
        $this->assertStringContainsString($expected, json_encode($msg, JSON_UNESCAPED_UNICODE));
    }



}
