<?php

require 'vendor/autoload.php';
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;

function getDirContents($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        $characterCount = strlen ( $path );
        if (!is_dir($path)) {
            if(substr($path, $characterCount-3, $characterCount) == "php"){
                $results[] = $path;
            }
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            if(substr($path, $characterCount-3, $characterCount) == "php"){
                $results[] = $path;
            }
        }
    }

    return $results;
}

//var_dump(getDirContents('testfolder/'));

function parser($directory){

    $all_php_paths = getDirContents('testfolder/');

    foreach ($all_php_paths as $key => $value) {
        $links_contents = file_get_contents($value);
        $code = $links_contents;
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            $ast = $parser->parse($code);
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }

        $dumper = new NodeDumper;
        echo $dumper->dump($ast) . "\n";
    }
}

parser('testfolder/')
?>