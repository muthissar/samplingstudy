<?php
require __DIR__ . '/../vendor/autoload.php';
use \App\Mut\DB;
use App\Mut\Config;
$ret = Config::getConfig();
$mutDb = new DB();
$mutDb->fill_samples(['ic_curve'=> 7]);