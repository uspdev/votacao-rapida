<?php
$hash = generateRandomString(20); //o tamanho 20 é fixo

// ja temos um hash gerado e o link encurtado correspondente
$hash = 'NAKYQOHCWDILQTRAYTZY';
$e = 'e.usp.br/fiw';

// se não quiser logo deixar em branco mas não sumir com a variável
$logo2 = __DIR__ . '/logo_eesc_horizontal.png';

$lista = 'João, Adriana, Maria, Carlos, Antônio, Marcela, Edson';
$tokens = gerarTokens(15, true);

$sessao = [
    '_type' => 'sessao', // fixo
    'unidade' => 'EESC',
    'ano' => 2020,
    'nome' => '339 Sessão do CTA',
    'hash' => $hash,
    'estado' => 'aberto', // 0 em elaboracao, 1 aberto, 2 finalizado
    'link_manual' => $e,
    'arq_tokens_pdf' => '',
    'lista' => $lista,
    'ownTokenList' => $tokens,
    'ownVotacaoList' => [
        [
            '_type' => 'votacao', // fixo
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
