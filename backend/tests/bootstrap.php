<?php

require __DIR__.'/../vendor/autoload.php';

$appKey = 'base64:'.base64_encode(random_bytes(32));

$_ENV['APP_KEY'] = $appKey;
$_SERVER['APP_KEY'] = $appKey;
putenv('APP_KEY='.$appKey);
