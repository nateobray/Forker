<?php

include 'vendor/autoload.php';

$forker = new \obray\Forker();
$forker->fork(function($pid){
    for($i=0; $i<10; ++$i){
        print_r($pid . " loop " . $i . "\n");
        sleep(1);
    }
});