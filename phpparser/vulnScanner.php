<?php

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
include("Parser.php");

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

// Runs vulnerability scanner
function parser($directory){
    $all_php_paths = getDirContents($directory);

    foreach ($all_php_paths as $key => $value) {
        // extending NodeVisitorAbstract to store the parent class
        // echo "\n" . "Scanning file " . str_replace(getcwd().'/', "", $value) . "\n";
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
        $linesSQL = $vuln2->linesSQL;
        $linesXSS = $vuln1->linesXSS;

        $sql = "[]";
        $xss = "[]";

        if(sizeof($linesSQL) > 0){
            echo $value;
            $sql = "[".implode(",", $linesSQL)."]";
            // for ($i = 0; $i < sizeof($linesSQL); $i++) {
            //     // echo "SQL Injection\n";
            //     // array_push($sql, $linesSQL[$i]);
            //     // echo "string";
                
            // }
        }

        if(sizeof($linesXSS) > 0){
            $xss = "[".implode(",", $linesXSS)."]";

            // for ($i = 0; $i < sizeof($linesXSS); $i++) {
            //     // echo "XSS vulnerability\n";
            //     // array_push($xss, $linesXSS[$i]);
            // }
        }

        // echo implode( ", ", $xss )."\n";
        
        if(sizeof($linesSQL) > 0 || sizeof($linesXSS) > 0){
            echo $value.":".$sql.";".$xss."\n";
        }
    }

}


parser($argv[1]);


