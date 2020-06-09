<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Uspdev\Votacao\View\Api;

class apiGerenteTests extends TestCase
{

    // // primeiro criar nova sessão
    // public function testCriarNovaSessao()
    // {
    //     // passando id = 0 no endpoit de obterSessao
    //     return true;
    // }

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

    public function testAdicionarEleitorSucesso(): void
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

        $expected = '{"status":"ok","data":"Eleitor excluído com sucesso."}';
        $this->assertEquals($expected, json_encode($sessao, JSON_UNESCAPED_UNICODE));
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
        $endpoint = '/gerente/listarTokens/'.$hash.'?codpes=' . $user;
        $tokens = Api::send($endpoint);

        // testou empty mas poderia testar algo melhor pois retorna a lista de tokens
        // ou vazio possivelmente
        $this->assertNotEmpty(json_encode($tokens, JSON_UNESCAPED_UNICODE));
        
    }

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
