<?php
//$hash = generateRandomString(20); //o tamanho 20 é fixo

// ja temos um hash gerado 
$hash = 'UUZEWSRWKBXOGJVWIYJV';

//e o link encurtado correspondente
//http://votacaorapida.eesc.usp.br/UUZEWSRWKBXOGJVWIYJV
$e = 'e.usp.br/fk5';

// se não quiser logo secundário, deixar em branco
$logo = __DIR__ . '/uspdev-logo.png';

// lista de nomes a associar aos tokens abertos
// $nomes = [];
require_once __DIR__ . '/demo-nomes.php';

//$tokens = gerarTokens(20, true);
require_once __DIR__ . '/demo-tokens.php';

// $votacoes = [];
require_once __DIR__ . '/demo-votacoes.php';

$sessao = [
    '_type' => 'sessao', // fixo
    'unidade' => 'USP',
    'ano' => 2020,
    'nome' => '1a. Sessão de votação',
    'quando' => 'aberto permanentemente',
    'hash' => $hash,
    'estado' => 'aberto', // 0 em elaboracao, 1 aberto, 2 finalizado
    'link' => $e,
    'email' => 'votacaorapida@eesc.usp.br',
    'logo' => $logo,
    'ownTokenList' => $tokens,
    'ownVotacaoList' => $votacoes,
];
