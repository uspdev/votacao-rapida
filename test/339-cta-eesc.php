<?php
$hash = generateRandomString(20); //o tamanho 20 é fixo

// ja temos um hash gerado e o link encurtado correspondente
$hash = 'NAKYQOHCWDILQTRAYTZY';
$e = 'e.usp.br/fjg';

// se não quiser logo, deixar em branco mas não sumir com a variável
$logo = __DIR__ . '/logo_eesc_horizontal.png';

$nomes = [
    'Edson Wendland',
    'Denis Coury',
    'Paulo Grecco',
    'Maria Bernardete',
    'Ana Lúcia',
    'José Setti',
    'Marco Terra',
    'Antonio',
    'Oscar',
    'Oswaldo',
    'Marcel',
    'Masaki',
];
$tokens = gerarTokens(17, true);

$sessao = [
    '_type' => 'sessao', // fixo
    'unidade' => 'EESC',
    'ano' => 2020,
    'nome' => '339a. Sessão do CTA',
    'hash' => $hash,
    'estado' => 'aberto', // 0 em elaboracao, 1 aberto, 2 finalizado
    'link' => $e,
    'logo' => $logo,
    'tokens_pdf' => '',
    'nomes_json' => json_encode($nomes),
    'ownTokenList' => $tokens,
    'ownVotacaoList' => [
        [
            '_type' => 'votacao', // fixo
            'estado' => 0, // sempre inicia fechado
            'nome' => 'APROVAÇÃO DA ATA',
            'descricao' => ' DA 338ª REUNIÃO DO CTA, DE 21/02/2020',
            'tipo' => 'aberta',
            'input_type' => 'checkbox',
            'input_count' => '1',
            'data_ini' => '',
            'data_fim' => '',
            'ownAlternativaList' => [
                [
                    '_type' => 'alternativa',
                    'texto' => 'Favorável',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Contrário',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Abstenção',
                ],
            ],
        ],
        [
            '_type' => 'votacao',
            'estado' => 0, // fechado
            'nome' => '1 - 20.1.341.18.7 - PROCESSO SELETIVO',
            'descricao' => ' DO DEPARTAMENTO DE ENGENHARIA ELÉTRICA E DE COMPUTAÇÃO. ABERTURA DE INSCRIÇÕES',
            'tipo' => 'aberta',
            'input_type' => 'radio',
            'input_count' => '1',
            'data_ini' => '',
            'data_fim' => '',
            'ownAlternativaList' => [
                [
                    '_type' => 'alternativa',
                    'texto' => 'Favorável',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Contrário',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Abstenção',
                ],
            ],
        ],
        [
            '_type' => 'votacao',
            'estado' => 0, // fechado
            'nome' => '2.1 - CREDENCIAMENTO DE DOCENTE NA CERT',
            'descricao' => '10.1.787.18.5 - ANDRÉ TEÓFILO BECK',
            'tipo' => 'aberta',
            'input_type' => 'radio',
            'input_count' => '1',
            'data_ini' => '',
            'data_fim' => '',
            'ownAlternativaList' => [
                [
                    '_type' => 'alternativa',
                    'texto' => 'Favorável',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Contrário',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Abstenção',
                ],
            ],
        ],
        [
            '_type' => 'votacao',
            'estado' => 0, // fechado
            'nome' => '2 - CREDENCIAMENTO DE DOCENTE NA CERT - EM BLOCO',
            'descricao' => '
            2.1 - 10.1.787.18.5 - ANDRÉ TEÓFILO BECK<br>
            2.2 - 12.1.3451.18.0 - VLADIMIR GUILHERME HAACH<br>
            2.3 - 14.1.1207.18.6 - JOSÉ ELIAS LAIER<br>
            2.4 - 98.1.1169.18.0 - MARIA DO CARMO CALIJURI<br>
            2.5 - 98.1.1191.18.6 - LUIZ ANTONIO DANIEL<br>
            2.6 - 01.1.1165.18.9 - EDSON CEZAR WENDLAND<br>
            2.7 - 11.1.1509.18.0 - LYDA PATRÍCIA SABOGAL PAZ<br>
            2.8 - 10.1.2744.18.1 - GHERHARDT RIBATSKI<br>
            2.9 - 08.1.3226.18.1 - CARLOS DIAS MACIEL
            ',
            'tipo' => 'aberta',
            'input_type' => 'radio',
            'input_count' => '1',
            'data_ini' => '',
            'data_fim' => '',
            'ownAlternativaList' => [
                [
                    '_type' => 'alternativa',
                    'texto' => 'Favorável',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Contrário',
                ],
                [
                    '_type' => 'alternativa',
                    'texto' => 'Abstenção',
                ],
            ],
        ],

    ],
];
