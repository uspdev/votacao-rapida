<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Uspdev\Votacao\View\Api;

class apiGerenteVotacaoTest extends TestCase
{
    public function user()
    {
        return '1575309';
    }

    public function listarSessao()
    {
        $endpoint = '/gerente/listarSessao?codpes=' . $this->user();
        $sessoes = Api::send($endpoint);
        return end($sessoes);
    }

    public function obterSessao()
    {
        $sessao_id = $this->listarSessao()->id;
        return Api::send('/gerente/sessao/' . $sessao_id . '?codpes=' . $this->user());
    }

    public function obterVotacao($sessao)
    {
        if (empty($sessao->ownVotacao)) {
            return false;
        }

        foreach ($sessao->ownVotacao as $v) {
            if ($v->nome == 'unit_test') {
                return $v;
            }
        }
        return false;
    }

    public function testAdicionarVotacaoSucesso()
    {
        $id = $this->obterSessao()->id;
        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $this->user();
        $data = [
            'acao' => 'adicionarVotacao',
            'nome' => 'unit_test',
            'descricao' => 'Unit test descrição',
            'tipo' => 'aberta',
            'ordem' => '1'
        ];
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"ok","data":"Votação adicionada com sucesso."}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testEditarVotacaoSucesso()
    {
        $sessao = $this->obterSessao();
        $votacao = $this->obterVotacao($sessao);

        $endpoint = '/gerente/sessao/' . $votacao->sessao_id . '?codpes=' . $this->user();
        $data = json_decode(json_encode($votacao), true);
        $data['descricao'] = 'Descrição alterada no unit test';
        $data['acao'] = 'editarVotacao';
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"ok","data":"Votação atualizada com sucesso."}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testEditarVotacaoNaoEncontrado()
    {
        $sessao = $this->obterSessao();
        $votacao = $this->obterVotacao($sessao);

        $endpoint = '/gerente/sessao/' . $votacao->sessao_id . '?codpes=' . $this->user();
        $data = json_decode(json_encode($votacao), true);
        $data['id'] = 200; // numero aleatorio
        $data['descricao'] = 'Descrição alterada no unit test';
        $data['acao'] = 'editarVotacao';
        $sessao = Api::send($endpoint, $data);

        $expected = 'não encontrada';
        $this->assertStringContainsString($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testEditarVotacaoMalFormado()
    {
        $sessao = $this->obterSessao();
        $votacao = $this->obterVotacao($sessao);

        $endpoint = '/gerente/sessao/' . $votacao->sessao_id . '?codpes=' . $this->user();
        $data = json_decode(json_encode($votacao), true);
        unset($data['id']);
        $data['descricao'] = 'Descrição alterada no unit test';
        $data['acao'] = 'editarVotacao';
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"erro","data":"Dados de editar votação mal formados"}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testRemoverVotacaoSucesso()
    {
        $sessao = $this->obterSessao();
        $votacao = $this->obterVotacao($sessao);

        $endpoint = '/gerente/sessao/' . $votacao->sessao_id . '?codpes=' . $this->user();
        $data = json_decode(json_encode($votacao), true);
        $data['acao'] = 'removerVotacao';
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"ok","data":"Votação removida com sucesso."}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    public function testRemoverVotacaoErroMalFormado()
    {
        $id = $this->obterSessao()->id;
        $endpoint = '/gerente/sessao/' . $id . '?codpes=' . $this->user();
        $data = [
            'acao' => 'removerVotacao',
            'nome' => 'unit_test',
            'descricao' => 'Unit test descrição',
            'tipo' => 'aberta'
        ];
        $sessao = Api::send($endpoint, $data);

        $expected = '{"status":"erro","data":"Dados de remover votação mal formados"}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }
}
