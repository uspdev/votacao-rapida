<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Uspdev\Votacao\View\Api;

class apiAdminTest extends TestCase
{
    public function testListarSessaoUsuarioInvalido(): void
    {
        $user = '1122'; //invalido
        $endpoint = '/admin/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $expected = '{"status":"erro","msg":"Usuário inválido"}';
        $this->assertEquals($expected, json_encode($sessoes, JSON_UNESCAPED_UNICODE));
    }

    public function testListarSessaoUsuarioSemAcesso(): void
    {
        $user = '111'; //sem acesso
        $endpoint = '/admin/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $expected = '{"status":"erro","msg":"Usuário sem privilégios"}';
        $this->assertEquals($expected, json_encode($sessoes, JSON_UNESCAPED_UNICODE));
    }

    public function testListarSessaoUsuarioAdmin(): void
    {
        $user = '1575309'; //sem acesso
        $endpoint = '/admin/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $this->assertGreaterThan(0, count((array) $sessoes));
    }

    public function testListarUsuarioUsuarioAdmin(): void
    {
        $user = '1575309'; //sem acesso
        $endpoint = '/admin/listarUsuario?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $this->assertGreaterThan(1, count((array) $sessoes));
    }

    public function testRotaInexistente(): void
    {
        $user = '1575309'; //sem acesso
        $endpoint = '/admin/naoexiste?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $expected = 'Metodo inexistente';
        $this->assertStringContainsString($expected, json_encode($sessoes));
    }
}
