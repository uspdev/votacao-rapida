<?php

require_once __DIR__ . '/../app/bootstrap.php';

echo "=== 1. Executando Migration Inicial ===" . PHP_EOL;
require __DIR__ . '/1_migration_inicial.php';

echo PHP_EOL . "=== 2. Executando Seed Inicial ===" . PHP_EOL;
require __DIR__ . '/2_seed_inicial.php';

echo PHP_EOL . "=== 3. Executando Migrations Incrementais ===" . PHP_EOL;
require __DIR__ . '/atualizar.php';

echo PHP_EOL . "Provisionamento concluído com sucesso!" . PHP_EOL;

