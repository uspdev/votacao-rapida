<?php
require_once __DIR__ . '/../app/bootstrap.php';
use Uspdev\Webservice\Auth;
use \RedBeanPHP\R as R;

Auth::salvarUsuario([
    'username' => 'admin',
    'pwd' => 'admin',
    'admin' => '1',
    'allow' => '']
);

R::selectDatabase('votacao');
R::useFeatureSet('latest');

R::exec('SET FOREIGN_KEY_CHECKS = 0;');
R::wipe('sessao');
R::wipe('votacao');
R::wipe('alternativa');
R::wipe('token');
R::exec('SET FOREIGN_KEY_CHECKS = 1;');
//R::nuke();

// vamos inserir dados de estado->acao
echo 'Inserindo dados de controle: ';
require_once __DIR__.'/../app/inserir_dados_de_controle.php';

$sessao = [
    '_type' => 'sessao',
    'unidade' => 'EESC',
    'ano' => 2020,
    'nome' => 'Primeira sesão de votação eletrônica',
    'hash' => 'hash001',
    'estado' => 'aberto', // 0 em elaboracao, 1 aberto, 2 finalizado
    'link_manual' => '',
    'lista' => 'João, Adriana, Maria, Carlos, Antônio, Carlito, Edson',
    'ownTokenList' => gerarTokens(10, true),
    'ownVotacaoList' => [
        [
            '_type' => 'votacao',
            'estado' => 0, // sempre inicia fechado
            'nome' => 'Votação para escolha do diretor da unidade.',
            'descricao' => '',
            'tipo' => 'fechada',
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
            'tipo' => 'aberta',
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
    'lista' => 'João, Adriana, Maria, Carlos, Antônio, Carlito, Edson',
    'ownTokenList' => gerarTokens(10, true),
];
R::store(R::dispense($sessao));

echo 'Dados de exemplo adicionados com sucesso', PHP_EOL;
