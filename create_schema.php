<?php
require __DIR__ . '/vendor/autoload.php';
use \App\Mut\MutDB;
$mutDb = new MutDB();
$mutDb->createDB();