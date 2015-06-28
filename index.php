<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';

use Http\RequestHandler;

$response = (new RequestHandler())
    ->receive($_POST)
    ->response();

echo $response;
