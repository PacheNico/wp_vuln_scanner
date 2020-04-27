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
    public $sqlVar;
    private $varCheck;

    // Sets class to either check the SQL statement or find the SQL variable
    function __construct($a1 = "") {
        $this->sqlVar = $a1;
        if (strlen($a1) > 0) {
            $this->varCheck = 1;
        } else {
            $this->varCheck = 0;
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
                    if ($this->sqlVar == $node->var->name) {
                        $parent = $node->getAttribute('parent');
                        if ($parent->expr->expr instanceof Node\Expr\BinaryOp\Concat) {
                            echo "VULNERABILITY DETECTED: SQL query formed by concatenation" . "\n";
                        }
                    }
                }
            }
        // SQL variable location
        } else {
            if ($node instanceof Node\Identifier && $node->name == "query") {
                $queryName = $node->name;
                $queryArgs = $node->getAttribute('parent')->args;
                $varName = array_values($queryArgs)[0]->value->name;
                $this->sqlVar = $varName;
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
        echo "\n" . "Filename: " . $value . "\n";
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
        $sqlVarArray = $vuln1->sqlVar;

        //traverse AST to find sql statements
        $vuln2 = new SQLVulnScan($sqlVarArray);
        $traverser2->addVisitor ($vuln2);
        $ast2 = $traverser2->traverse($ast);
    }

}


parser($argv[1]);





