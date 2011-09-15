<?php

// Path to the less.php library files
$lessLibraryPath = __DIR__.'/../lib/';

// Path to the css cache directory
$cachePath = __DIR__.'/cache/';

// Register an autoload function
spl_autoload_register(function($className) use ($lessLibraryPath) {
    $fileName = $lessLibraryPath.str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (file_exists($fileName)) {
        require_once $fileName;
    }
});

// Create our environment
$env = new \Less\Environment;
$env->setCompress(true);

// Grab a comma separated list of files to parse from the query string.
$files = explode(',', isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');

// Only allow inclusion of .less files in this directory
foreach($files as $key => $file) {
    $files[$key] = pathinfo($file, PATHINFO_BASENAME);
    if (!file_exists($files[$key])) {
        unset($files[$key]);
    }
    if (pathinfo($file, PATHINFO_EXTENSION) != 'less') {
        unset($files[$key]);
    }
}

if (count($files)) {
    
    // Check for a cached version of the query string hash
    $hash = md5(array_reduce($files, function($a, $b) {
        return $a . $b . filemtime($b);
    }));

    if ( ! file_exists($cachePath.$hash)) {

        // Parse the selected files
        $parser = new \Less\Parser($env);
        foreach($files as $file) {
            try {
                $parser->parseFile($file);
            } catch (\Exception $e) {
                // Skip errors for now
            }
        }
        file_put_contents($cachePath.$hash, $parser->getCss());
    }

    $modTime = filemtime($cachePath.$hash);

    // Send 304 header if appropriate
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'], $_SERVER['SERVER_PROTOCOL'])) {
        if ($modTime <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            header("{$_SERVER['SERVER_PROTOCOL']} 304 Not Modified");
            exit;
        }
    } else {

        // Output the parsed content
        header('Content-Type: text/css');
        header('Last-Modified: '.date('r', $modTime));
        ob_clean();
        flush();
        readfile($cachePath.$hash);
        exit;

    }

}

header("{$_SERVER['SERVER_PROTOCOL']} 404 Page Not Found");
