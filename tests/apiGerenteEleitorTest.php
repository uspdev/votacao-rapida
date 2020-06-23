<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Uspdev\Votacao\View\Api;

class apiGerenteEleitorTest extends TestCase
{
    public function testAdicionarEleitorSucesso()
    {
        $user = '1575309'; // para listar sessoes
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $id = $sessao->id;

        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user;
        $data = [
            'acao' => 'adicionarEleitor',
            'apelido' => 'unit_test',
            'nome' => 'Unit test',
            'email' => 'kawabata+unit-test@usp.br'
        ];
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"ok","data":"Eleitor inserido com sucesso."}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testAdicionarEleitorJaExiste(): void
    {
        $user = '1575309'; // para listar sessoes
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $id = $sessao->id;

        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user;
        $data = [
            'acao' => 'adicionarEleitor',
            'apelido' => 'unit_test',
            'nome' => 'Unit test',
            'email' => 'kawabata+unit-test@usp.br'
        ];
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"erro","data":"Eleitor já existe"}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testRemoverEleitorNaoExiste1(): void
    {
        $user = '1575309'; // para listar sessoes
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $id = $sessao->id;

        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user;
        $data = [
            'acao' => 'removerEleitor',
            'apelido' => 'unit_test',
            'nome' => 'Unit test',
            'email' => 'kawabata+unit-test@usp.br'
        ];
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"erro","data":"Eleitor não existe nessa sessão"}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testEditarEleitorSucesso(): void
    {
        $user = '1575309'; // para listar sessoes
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $id = $sessao->id;

        // vamos procurar o eleitor inserido antes para poder remover
        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user;
        $sessao = Api::send($endpoint);
        $eleitor = '';
        foreach ($sessao->ownToken as $e) {
            if ($e->apelido == 'unit_test') {
                $eleitor = $e;
                break;
            }
        }

        $data = json_decode(json_encode($eleitor), true);
        $data['nome'] = 'Nome editado';
        $data['acao'] = 'editarEleitor';
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"ok","data":"Eleitor atualizado com sucesso."}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testRemoverEleitorSucesso(): void
    {
        $user = '1575309'; // para listar sessoes
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $id = $sessao->id;

        // vamos procurar o eleitor inserido antes para poder remover
        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $user;
        $sessao = Api::send($endpoint);
        $eleitor = '';
        foreach ($sessao->ownToken as $e) {
            if ($e->apelido == 'unit_test') {
                $eleitor = $e;
                break;
            }
        }

        $data = json_decode(json_encode($eleitor), true);
        $data['acao'] = 'removerEleitor';
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"ok","data":"Eleitor removido com sucesso."}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }
}
