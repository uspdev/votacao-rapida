<?php
$votacoes = [
    [
        '_type' => 'votacao', // fixo
        'estado' => 0, // sempre inicia fechado
        'nome' => 'Qual a sua primeira impressão ao utilizar a votação rápida?',
        'descricao' => 'Assinale uma alternativa',
        'tipo' => 'aberta',
        'input_type' => 'checkbox',
        'input_count' => '1',
        'data_ini' => '',
        'data_fim' => '',
        'ownAlternativaList' => [
            [
                '_type' => 'alternativa',
                'texto' => 'Não gostei',
            ],
            [
                '_type' => 'alternativa',
                'texto' => 'Achei legalzinho',
            ],
            [
                '_type' => 'alternativa',
                'texto' => 'Achei excelente',
            ],
            [
                '_type' => 'alternativa',
                'texto' => 'Vai resolver todos os problemas do mundo!',
            ],
        ],
    ],
    [
        '_type' => 'votacao',
        'estado' => 0,
        'nome' => 'Qual a sua segunda impressão ao utilizar a votação rápida?',
        'descricao' => 'Agora que você já viu o processo completo da votação, assinale a opção que reflete seu grau de satisfação.',
        'tipo' => 'fechada',
        'input_type' => 'radio',
        'input_count' => '1',
        'data_ini' => '',
        'data_fim' => '',
        'ownAlternativaList' => [
            [
                '_type' => 'alternativa',
                'texto' => 'Realmente não gostei, precisa melhorar.',
            ],
            [
                '_type' => 'alternativa',
                'texto' => 'Agora que ví o funcionamento, acho que vai ajudar bastante.',
            ],
            [
                '_type' => 'alternativa',
                'texto' => 'Tenho certeza que a votação rápida vai trazer a paz mundial!',
            ],
        ],
    ]
];
