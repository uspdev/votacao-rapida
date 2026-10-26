<?php
/**
 * Script de atualização incremental de banco de dados e controle de migrations.
 *
 * Cria a tabela de controle `migration` (se não existir) e executa somente
 * os arquivos de migração pendentes, registrando a data e hora de execução.
 *
 * Uso:
 *   php sql/atualizar.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::selectDatabase('votacao');
R::freeze(false);

echo "=====================================================" . PHP_EOL;
echo "  Votação Rápida — Atualizador de Migrations (SQL)  " . PHP_EOL;
echo "=====================================================" . PHP_EOL . PHP_EOL;

// 1. Cria a tabela de controle de migrations se não existir
try {
    R::exec("
        CREATE TABLE IF NOT EXISTS `migration` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `nome` VARCHAR(191) NOT NULL UNIQUE,
            `aplicada_em` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (\Exception $e) {
    echo "Erro ao criar/verificar tabela de migrations: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// 2. Se o banco estiver completamente vazio, orienta provisionar
$tables = R::inspect();
if (empty($tables) || (count($tables) === 1 && isset($tables['migration']))) {
    echo "Aviso: O banco de dados está vazio." . PHP_EOL;
    echo "Para a primeira instalação, execute: php sql/provisionar.php" . PHP_EOL;
    exit(0);
}

// 3. Reconhecimento automático de migrations legadas já presentes no banco
$colunasVotacao = R::inspect('votacao');
if (in_array('ordem', $colunasVotacao)) {
    if (!R::findOne('migration', 'nome = ?', ['3_migration_2020-06-17.php'])) {
        $m = R::dispense('migration');
        $m->nome = '3_migration_2020-06-17.php';
        $m->aplicada_em = date('Y-m-d H:i:s');
        R::store($m);
    }
}
if (in_array('obs', $colunasVotacao)) {
    if (!R::findOne('migration', 'nome = ?', ['4_migration_2020-06-30.php'])) {
        $m = R::dispense('migration');
        $m->nome = '4_migration_2020-06-30.php';
        $m->aplicada_em = date('Y-m-d H:i:s');
        R::store($m);
    }
}
$acao7 = R::findOne('acao', 'cod = 7');
if ($acao7 && $acao7->nome === 'Esconder resultado') {
    if (!R::findOne('migration', 'nome = ?', ['5_migration_2026-10-26_acao_esconder_resultado.php'])) {
        $m = R::dispense('migration');
        $m->nome = '5_migration_2026-10-26_acao_esconder_resultado.php';
        $m->aplicada_em = date('Y-m-d H:i:s');
        R::store($m);
    }
}

// 4. Busca todos os arquivos de migração incrementais (exclui a inicial 1_)
$arquivos = glob(__DIR__ . '/*_migration_*.php');
$arquivos = array_filter($arquivos, function ($f) {
    return basename($f) !== '1_migration_inicial.php';
});
natsort($arquivos);

echo "Verificando status das migrations..." . PHP_EOL . PHP_EOL;

$pendentes = 0;
foreach ($arquivos as $arquivo) {
    $nome = basename($arquivo);
    $registro = R::findOne('migration', 'nome = ?', [$nome]);

    if ($registro) {
        echo "  [ OK ] Já aplicada: {$nome} (em {$registro->aplicada_em})" . PHP_EOL;
    } else {
        echo "  [ NOVA ] Aplicando: {$nome}..." . PHP_EOL;
        try {
            require $arquivo;

            $m = R::dispense('migration');
            $m->nome = $nome;
            $m->aplicada_em = date('Y-m-d H:i:s');
            R::store($m);

            echo "          -> Sucesso! Registrada no banco." . PHP_EOL;
            $pendentes++;
        } catch (\Exception $e) {
            echo "          -> ERRO ao aplicar {$nome}: " . $e->getMessage() . PHP_EOL;
            exit(1);
        }
    }
}

echo PHP_EOL;
if ($pendentes === 0) {
    echo "Tudo em ordem! Nenhuma migration pendente." . PHP_EOL;
} else {
    echo "Concluído: {$pendentes} nova(s) migration(s) aplicada(s) com sucesso!" . PHP_EOL;
}
echo PHP_EOL;

