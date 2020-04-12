<?php
require_once __DIR__ . '/../app/bootstrap.php';

use \RedBeanPHP\R as R;

R::setup(getenv('DB_DSN'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
$listOfTables = R::inspect();
print_r($listOfTables);
