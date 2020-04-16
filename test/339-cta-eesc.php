<?php
$hash = generateRandomString(20); //o tamanho 20 é fixo

// ja temos um hash gerado e o link encurtado correspondente
$hash = 'NAKYQOHCWDILQTRAYTZY';
$e = 'e.usp.br/fjg';

// se não quiser logo, deixar em branco mas não sumir com a variável
$logo = __DIR__ . '/logo_eesc_horizontal.png';

// $nomes = [];
require_once __DIR__ . '/339-nomes.php';

//$tokens = gerarTokens(17, true);
require_once __DIR__ . '/339-tokens.php';

// $votacoes = [];
require_once __DIR__ . '/339-votacoes.php';

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
    'ownVotacaoList' => $votacoes,
];
