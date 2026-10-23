<?php

require_once __DIR__ . '/../app/bootstrap.php';

echo "=== 1. Executando Migration Inicial ===" . PHP_EOL;
require __DIR__ . '/1_migration_inicial.php';

echo PHP_EOL . "=== 2. Executando Seed Inicial ===" . PHP_EOL;
require __DIR__ . '/2_seed_inicial.php';

echo PHP_EOL . "=== 3. Executando Migration (2020-06-17: ordem) ===" . PHP_EOL;
require __DIR__ . '/3_migration_2020-06-17.php';

echo PHP_EOL . "=== 4. Executando Migration (2020-06-30: obs) ===" . PHP_EOL;
require __DIR__ . '/4_migration_2020-06-30.php';

echo PHP_EOL . "Provisionamento concluído com sucesso!" . PHP_EOL;

