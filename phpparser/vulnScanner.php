<?php

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
require_once('SQLParser.php');


// Gets the source code of all PHP files in a directory
function getDirContents($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        $characterCount = strlen ( $path );
        if (!is_dir($path)) {
            if(substr($path, $characterCount-4, $characterCount) == ".php"){
                $results[] = $path;
            }
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            if(substr($path, $characterCount-4, $characterCount) == ".php"){
                $results[] = $path;
            }
        }
    }

    return $results;
}

// Runs vulnerability scanner
function parser($directory){
    $all_php_paths = getDirContents($directory);

    // iterating through all php paths
    foreach ($all_php_paths as $key => $value) {
        // extending NodeVisitorAbstract to store the parent class
        echo "\n" . "Scanning file " . str_replace(getcwd().'/', "", $value) . "\n";
        $links_contents = file_get_contents($value);
        $code = $links_contents;

        // obtain AST
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            $ast = $parser->parse($code);
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }

        $traverser1 = new NodeTraverser();
        $traverser2 = new NodeTraverser();

        // traverse AST to find sqlVars
        $vuln1 = new SQLVulnScan();
        $traverser1->addVisitor ($vuln1);
        $ast1 = $traverser1->traverse($ast);
        $sqlVarArray = $vuln1->sqlVars;

        //traverse AST to find sql statements
        $vuln2 = new SQLVulnScan($sqlVarArray);
        $traverser2->addVisitor ($vuln2);
        $ast2 = $traverser2->traverse($ast);
        $statements = $vuln2->sqlStatements;
        for ($i = 0; $i < sizeof($statements); $i++) {
            echo $statements[$i] . "\n";
        }
        $isVuln = $vuln2->isVuln;
        $lines = $vuln2->lines;

        if($isVuln){
            // echo "\tWARNING, Concatenating SQL statement detected, Possible SQL Injection\n";
            // $lines = $as2->lines;
            // echo "\tFound in line ".$line." of ".$value;

            for ($i = 0; $i < sizeof($lines); $i++) {
                echo "\tWARNING, Concatenating SQL statement detected, Possible SQL Injection\n";
                $line = $lines[0];
                echo "\tFound in line ".$line." of ".explode("/", $value)[sizeof(explode("/", $value))-1]."\n";
            }
        }

    }

}


parser($argv[1]);






