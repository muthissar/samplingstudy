<?php
require __DIR__ . '/vendor/autoload.php';
use App\Mut\DB;
$mutDb = new DB();
$mutDb->drop();