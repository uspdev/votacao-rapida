<?php
require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;
echo "Conectando em ", getenv('DB_DSN'), PHP_EOL;
R::setup(getenv('DB_DSN'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
$listOfTables = R::inspect();
print_r($listOfTables);
