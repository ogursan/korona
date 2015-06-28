<?php
define('DEBUG', false);

spl_autoload_register(function($className){
    $className = trim($className, '\\');
    $path = str_replace('\\', '/', $className) . '.php';

    include $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $path;
});

use Database\DB;

DB::initialize('localhost', '3306', 'korona', 'root', '');


