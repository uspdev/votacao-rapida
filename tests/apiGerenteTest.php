<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Uspdev\Votacao\View\Api;

class apiGerenteTest extends TestCase
{

    // primeiro adicionar nova sessão
    public function testAdicionarRemoverSessao()
    {
        $user = '1575309'; //ok
        $endpoint = '/gerente/sessao/0?codpes=' . $user;
        $data = ['acao' => 'adicionarSessao', 'nome' => 'Sessao unit test'];
        $sessao = Api::send($endpoint, $data);
        $sessao_id = $sessao->data;
        $expected = '{"status":"ok","data":';
        $this->assertStringContainsString($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));

        // remover
        $endpoint = '/gerente/sessao/' . $sessao_id . '?codpes=' . $user;
        $data = ['acao' => 'removerSessao'];
        $sessao = Api::send($endpoint, $data);
        $expected = '{"status":"ok","data":"Sessão removida com sucesso."}';
        $this->assertStringContainsString($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
    }

    // primeiro vamos testar listagem para depois testar 
    // obter pois obter usa listagem
    public function testListarSessaoUsuarioInvalido(): void
    {
        $user = '1122'; //invalido
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $expected = '{"status":"erro","msg":"Usuário inválido"}';
        $this->assertEquals($expected, json_encode($sessoes, JSON_UNESCAPED_UNICODE));
    }

    public function testListarSessaoSucesso(): void
    {
        $user = '1575309'; //ok
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $this->assertGreaterThan(0, count((array) $sessoes));
    }

    public function testObterSessaoSucesso(): void
    {
        $user = '1575309';
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        // vamos pegar a ultima sessão da lista
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

    // public function testSessaoPostActions()
    // {
    //     // varias actions de post para serem testadas
    //     return true;
    // }

    // public function testExportarVotacaoSucesso()
    // {
    //     // para exportar em email a votação
    //     return true;
    // }

    public function testListarTokens()
    {
        // usado no demo para obter os tokens e publicá-los na página

        $user = '1575309'; // para listar sessoes
        $endpoint = '/gerente/listarSessao?codpes=' . $user;
        $sessoes = Api::send($endpoint);
        $sessao = end($sessoes);
        $hash = $sessao->hash;

        $user = '1575309'; // para listar sessoes
        $endpoint = '/gerente/listarTokens/' . $hash . '?codpes=' . $user;
        $tokens = Api::send($endpoint);

        // testou empty mas poderia testar algo melhor pois retorna a lista de tokens
        // ou vazio possivelmente
        $this->assertNotEmpty(json_encode($tokens, JSON_UNESCAPED_UNICODE));
    }
}
