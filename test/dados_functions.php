<?php
require_once __DIR__ . '/../app/bootstrap.php';

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
        ['_type' => 'acao', 'cod' => 6, 'estado' => '2', 'nome' => 'Reiniciar', 'msg' => 'Votação reiniciada', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 7, 'estado' => '5', 'nome' => 'Finalizar', 'msg' => 'Votação finalizada', 'escopo' => 'apoio'],
        ['_type' => 'acao', 'cod' => 8, 'estado' => '', 'nome' => 'Responder', 'msg' => 'Resposta aceita', 'escopo' => 'votacao'],

    ];

    foreach ($estados as $e) {
        R::store(R::dispense($e));
    }
    foreach ($acoes as $a) {
        R::store(R::dispense($a));
    }

    return 'Dados de controle inseridos com sucesso';
}

function dadosDeExemplo($hash)
{
    $sessao = [
        '_type' => 'sessao',
        'unidade' => 'EESC',
        'ano' => 2020,
        'nome' => '339 Sessão do CTA',
        'hash' => $hash,
        'estado' => 'aberto', // 0 em elaboracao, 1 aberto, 2 finalizado
        'link_manual' => 'http://e.usp.br/fik',
        'arq_tokens_pdf' => '',
        'lista' => 'João, Adriana, Maria, Carlos, Antônio, Marcela, Edson',
        'ownTokenList' => gerarTokens(10, true),
        'ownVotacaoList' => [
            [
                '_type' => 'votacao',
                'estado' => 0, // sempre inicia fechado
                'nome' => 'Homologação de relatório - SHS',
                'descricao' => 'Processo seletivo do SHS',
                'tipo' => 'aberta',
                'input_type' => 'checkbox',
                'input_count' => '3',
                'data_ini' => '',
                'data_fim' => '',
                'ownAlternativaList' => [
                    [
                        '_type' => 'alternativa',
                        'texto' => 'Armando Sales',
                    ],
                    [
                        '_type' => 'alternativa',
                        'texto' => 'Oswaldo Cruz',
                    ],
                    [
                        '_type' => 'alternativa',
                        'texto' => 'Dom Pedro',
                    ],
                    [
                        '_type' => 'alternativa',
                        'texto' => 'Marie Curie',
                    ],
                ],
            ],
            [
                '_type' => 'votacao',
                'estado' => 0, // fechado
                'nome' => 'Você é a favor de usar votação eletrônica?',
                'descricao' => '',
                'tipo' => 'fechada',
                'input_type' => 'radio',
                'input_count' => '1',
                'data_ini' => '',
                'data_fim' => '',
                'ownAlternativaList' => [
                    [
                        '_type' => 'alternativa',
                        'texto' => 'Sim',
                    ],
                    [
                        '_type' => 'alternativa',
                        'texto' => 'Não',
                    ],
                ],
            ],

        ],
    ];
    R::store(R::dispense($sessao));

    $sessao = [
        '_type' => 'sessao',
        'unidade' => 'EESC',
        'ano' => 2020,
        'nome' => 'Segunda sesão de votação eletrônica',
        'hash' => 'hash002',
        'estado' => 'fechado',
        'link_qrcode' => '',
        'link_manual' => '',
        'lista' => '',
        'ownTokenList' => gerarTokens(10, true),
    ];
    R::store(R::dispense($sessao));

    return 'Dados de exemplo adicionados com sucesso';
}

function gerarPDF($hash)
{
    R::selectDatabase('votacao');
    R::useFeatureSet('latest');

    $sessao = R::findOne('sessao', 'hash = ?', [$hash]);

    $logo2 = __DIR__ . '/../template/logo_eesc_horizontal.png';

    $filename = gerarListaQrcodePdf($sessao, $logo2);

    $sessao->arq_tokens_pdf = $filename;
    R::store($sessao);

    return  $filename . ' gerado com sucesso';
}
