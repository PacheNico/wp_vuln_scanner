<?php
// Import libraries
require 'vendor/autoload.php';
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeFinder;

// Scanner class 
class sqlVulnScan extends NodeVisitorAbstract {

    private $stack;
    public $sqlVars;
    private $varCheck;
    public $sqlStatements;
    public $isVuln = false;

    // Sets class to either check the SQL statement or find the SQL variable
    function __construct($a1=[]) {
        $this->sqlVars = $a1;
        $this->sqlStatements = [];
        if (count($a1) == 0) {
            $this->varCheck = 0;
        } else {
            $this->varCheck = 1;
        }
    }

    // Sets the stack to an empty array before traversal
    public function beforeTraverse(array $nodes) {
        $this->stack = [];
    }

    // Runs checks for SQL variables or SQL statements
    public function enterNode(Node $node) {

        // Stores parent node
        if (!empty($this->stack)) {
            $node->setAttribute('parent', $this->stack[count($this->stack)-1]);
        }
        $this->stack[] = $node;

        // SQL statement check
        if ($this->varCheck == 1) {
            if ($node instanceof Node\Expr\Assign){
                if ($node->var instanceof Node\Expr\Variable){
                    for ($i = 0; $i < sizeof($this->sqlVars); $i++) {
                        if ($this->sqlVars[$i] == $node->var->name) {
                            $parent = $node->getAttribute('parent');
                            if ($parent->expr->expr instanceof Node\Expr\BinaryOp\Concat) {
                                $this->isVuln = true;
                                array_push($this->sqlStatements, $parent->expr->expr->left->value);
                            }
                        }
                    }
                }
            }
        // SQL variable location
        } else{
            if ($node instanceof Node\Identifier && $node->name == "query") {
                $queryName = $node->name;
                $queryArgs = $node->getAttribute('parent')->args; 
                $dumper = new NodeDumper;      
                echo $dumper->dump($queryArgs) . "\n";      
                $varName = array_values($queryArgs)[0]->value->name;
<<<<<<< HEAD
   
                $this->sqlVar = $varName;
=======
                array_push($this->sqlVars, $varName);
>>>>>>> 3cf18ff304f31a22325f6d39aeec23d56b619cb6
            }
        }
    }

    // Pops the node
    public function leaveNode(Node $node) {
        array_pop($this->stack);
    }

}

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

function getLineWithString($fileName, $str) {
    $lines = file($fileName);
    $idx = 1;
    foreach ($lines as $lineNumber => $line) {
        if (strpos($line, $str) !== false) {
            return $idx;
        }
        $idx++;
    }
    return -1;
}

// Runs vulnerability scanner
function parser($directory){
    $all_php_paths = getDirContents($directory);

    foreach ($all_php_paths as $key => $value) {
        // extending NodeVisitorAbstract to store the parent class
        //echo "\n" . "Scanning file " . str_replace(getcwd().'/', "", $value) . "\n";
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
            //echo $statements[$i] . "\n";
        }
        $isVuln = $vuln2->isVuln;

        if($isVuln){
<<<<<<< HEAD
            echo "\tWARNING, Concatenating SQL statement detected, Possible SQL Injection\n";
            $line = getLineWithString($value, $statement);
            echo "\tFound in line ".$line." of ".$value;
=======
            for ($i = 0; $i < sizeof($statements); $i++) {
                echo "\tWARNING, Concatenating SQL statement detected, Possible SQL Injection\n";
                $line = getLineWithString($value, $statements[$i]);
                echo "\tFound in".$statements[$i]." line ".$line." of ".$value."\n";
            }
>>>>>>> 3cf18ff304f31a22325f6d39aeec23d56b619cb6
        }

    }

}


parser($argv[1]);






