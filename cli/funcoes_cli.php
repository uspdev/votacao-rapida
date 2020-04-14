<?php

use Uspdev\Webservice\Auth;
use \RedBeanPHP\R as R;

function salvarUsuarioApi()
{
    Auth::salvarUsuario(
        [
            'username' => 'admin',
            'pwd' => 'admin',
            'admin' => '1',
            'allow' => ''
        ]
    );
    return 'Usuário da api cadastrado';
}

function limparBD()
{
    R::selectDatabase('votacao');
    R::useFeatureSet('latest');

    R::exec('SET FOREIGN_KEY_CHECKS = 0;');
    R::wipe('sessao');
    R::wipe('votacao');
    R::wipe('alternativa');
    R::wipe('resposta');
    R::wipe('token');
    R::exec('SET FOREIGN_KEY_CHECKS = 1;');
    //R::nuke();

    return 'Banco de dados apagado com sucesso.';
}

function dadosDeControle()
{
    // estes dados são de controle da votação 
    // e devem existir no banco de dados

    R::selectDatabase('votacao');
    R::useFeatureSet('latest');

    R::wipe('estado');
    R::wipe('acao');

    $estados = [
        ['_type' => 'estado', 'cod' => 0, 'nome' => 'Fechada', 'acoes' => '0', 'tabela' => 'votacao'],
        ['_type' => 'estado', 'cod' => 1, 'nome' => 'Em exibição', 'acoes' => '1,2', 'tabela' => 'votacao'],
        ['_type' => 'estado', 'cod' => 2, 'nome' => 'Em votação', 'acoes' => '3', 'tabela' => 'votacao'],
        ['_type' => 'estado', 'cod' => 3, 'nome' => 'Em pausa', 'acoes' => '4,5,6', 'tabela' => 'votacao'],
        ['_type' => 'estado', 'cod' => 4, 'nome' => 'Resultado', 'acoes' => '7', 'tabela' => 'votacao'],
        ['_type' => 'estado', 'cod' => 5, 'nome' => 'Finalizado', 'acoes' => '4', 'tabela' => 'votacao'],
    ];

    $acoes = [
        ['_type' => 'acao', 'cod' => 0, 'estado' => '1', 'nome' => 'Mostrar', 'msg' => 'Votação em tela', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 1, 'estado' => '0', 'nome' => 'Esconder', 'msg' => 'Votação fechada', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 2, 'estado' => '2', 'nome' => 'Iniciar', 'msg' => 'Votação aberta', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 3, 'estado' => '3', 'nome' => 'Pausar', 'msg' => 'Votação pausada', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 4, 'estado' => '4', 'nome' => 'Mostrar resultado', 'msg' => 'Mostrando resultados', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 5, 'estado' => '2', 'nome' => 'Continuar', 'msg' => 'Votação aberta', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 6, 'estado' => '1', 'nome' => 'Reiniciar', 'msg' => 'Votação reiniciada', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 7, 'estado' => '5', 'nome' => 'Finalizar', 'msg' => 'Votação finalizada', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 8, 'estado' => '', 'nome' => 'Responder', 'msg' => 'Resposta aceita', 'escopo' => 'votacao'],

    ];

    foreach ($estados as $e) {
        R::store(R::dispense($e));
    }
    foreach ($acoes as $a) {
        R::store(R::dispense($a));
    }

    return 'Dados de controle inseridos/atualizados com sucesso';
}

function importareSubstituirDadosDeSessao($arq)
{
    require_once $arq;

    $s = R::findOne('sessao', 'hash = ?', [$hash]);
    if ($s) {
        excluirSessao($hash);
    }
    $sessao = R::dispense($sessao);
    $id = R::store($sessao);
    $sessao = R::load('sessao', $id);

    $arq = gerarListaQrcodePdf($sessao, $logo2);
    $sessao->arq_tokens_pdf = $arq;
    R::store($sessao);

    return 'Dados importados com sucesso';
}

function excluirSessao($hash)
{
    if ($sessao = R::findOne('sessao', 'hash = ?', [$hash])) {

        // vamos limpar tokens
        R::trashAll($sessao->ownTokenList);

        $votacoes = $sessao->ownVotacaoList;
        foreach ($votacoes as $votacao) {
            // alternativas e respostas
            R::trashAll($votacao->ownAlternativaList);
            R::trashAll($votacao->ownRespostaList);
        }
        // votacoes
        R::trashAll($votacoes);

        // arquivos
        if (is_file(ARQ . '/' . $sessao->arq_tokens_pdf)) {
            unlink(ARQ . '/' . $sessao->arq_tokens_pdf);
        }

        // e finalmente a sessao
        R::trash($sessao);

        return 'Sessão excluída com sucesso';
    } else {
        return 'Nada para excluir';
    }
}

function listarSessoes()
{
    $sessoes = R::findAll('sessao');
    foreach ($sessoes as $sessao) {
        $out[] = $sessao->nome . '->' . $sessao->hash;
    }
    return $out;
}

function gerarVotosAleatorios($hash)
{
    // vamos pegar o tipo de votação que estiver 'em votação'
    $em_votacao = 2;
    $tipo = R::getCell('SELECT tipo FROM votacao WHERE estado = ?', [$em_votacao]);
    if (!$tipo) {
        echo 'Sem votação aberta.', PHP_EOL;
        return;
    }

    // e pegar todos os tokens desse tipo
    $sessao_tmp = R::findOne('sessao', 'hash = ?', [$hash]);
    $tokens = R::find('token', "sessao_id = ? and tipo = ?", [$sessao_tmp->id, $tipo]);
    $tokens = R::exportAll($tokens);

    // vamos votar
    foreach ($tokens as $token) {
        $sessao = obterSessao($hash, $token['token']);

        $voto = gerarVotoAleatorio($sessao->render_form);
        echo 'Voto enviado do token ', $token['token'], PHP_EOL;
        echo json_encode($voto), PHP_EOL, PHP_EOL;

        $voto = post($hash, $token['token'], $voto);
        echo 'voto recebido', PHP_EOL;
        echo json_encode($voto), PHP_EOL, PHP_EOL;
    }
}


function gerarVotoAleatorio($votacao)
{
    $ids = array_column($votacao->alternativas, 'id');
    $voto['acao'] = '8';
    $voto['votacao_id'] = $votacao->id;
    $voto['alternativa_id'] = $ids[rand(0, count($ids) - 1)];
    return $voto;
}
