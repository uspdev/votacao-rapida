<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../cli/funcoes_cli.php';

if (!empty($argv[1]) && !empty($argv[2])) {
    $quant = $argv[1];
    $file = $argv[2];
    $tokens = gerarTokens($quant, true);

    $f = varexport($tokens, true);
    $f = '<?php
    $tokens = 
    ' . $f . ';
    ';
    file_put_contents($file, $f);
    echo 'Tokens gerados com sucesso.', PHP_EOL;
} else {
    echo 'Uso: php gerarTokens.php <quantidade> <nome-do-arquivo.php>', PHP_EOL;
}

/**
 * var_export() with square brackets and indented 4 spaces.
 */

function varexport($expression, $return = FALSE)
{
    $export = var_export($expression, TRUE);
    $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
    $array = preg_split("/\r\n|\n|\r/", $export);
    $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
    $export = join(PHP_EOL, array_filter(["["] + $array));
    if ((bool) $return) return $export;
    else echo $export;
}
