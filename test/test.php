<?php

include 'vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$forker = new \obray\Forker();
$forker->fork(function($pid, $queue){
    
});