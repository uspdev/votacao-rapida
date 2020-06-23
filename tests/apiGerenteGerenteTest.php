<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Uspdev\Votacao\View\Api;

class apiGerenteGerenteTest extends TestCase
{
    public function testAdicionarGerenteSucesso()
    {
        $user = '1575309'; // para listar sessoes
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $id = $sessao->id;

        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user;
        $data = [
            'acao' => 'adicionarGerente',
            'codpes' => '1112',
        ];
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"ok","data":"Gerente adicionado com sucesso."}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testRemoverGerenteSucesso()
    {
        $user = '1575309'; // para listar sessoes
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $id = $sessao->id;
        $sessao = Api::send('/gerente/sessao/'.$id.'?codpes='.$user);
        $gerentes = $sessao->sharedUsuario;
        foreach ($gerentes as $g) {
            if ($g->codpes == '1112') {
                $gerente_id = $g->id;
                break;
            }
        }

        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user;
        $data = [
            'acao' => 'removerGerente',
            'id' => $gerente_id,
        ];
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"ok","data":"Gerente removido com sucesso."}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }
}
