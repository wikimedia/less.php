<?php

function loadLessClass($className)
{
    $fileName = __DIR__.'/../lib/'.str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (file_exists($fileName)) {
        require_once $fileName;
    }
}
spl_autoload_register('loadLessClass');
