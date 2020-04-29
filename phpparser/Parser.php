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
use PhpParser\PrettyPrinter;

// Scanner class 
class sqlVulnScan extends NodeVisitorAbstract {

    private $stack;
    public $sqlVars;
    private $varCheck;
    public $sqlStatements;
    public $isVuln = false;
    public $linesSQL = array();
    public $linesXSS = array();

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
        $this->sources = array("_REQUEST", "_GET", "_POST");    // fill in
        $this->sinks = array("PhpParser\Node\Stmt\Echo_","PhpParser\Node\Expr\Exit_", "PhpParser\Node\Expr\Print_", "PhpParser\Node\Expr\FuncCall");    // fill in
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
                                array_push($this->sqlStatements, $parent->getLine());
                                array_push($this->linesSQL, $node->getLine());
                            }
                        }
                    }
                }
            }
        // SQL variable location
        } else{
            if ($node instanceof Node\Identifier && $node->name == "query") {
                $parent = $node->getAttribute('parent');
                if ($parent instanceof Node\Expr\MethodCall && $parent->args != null) {
                    $queryArgs = $parent->args[0];
                    if ($queryArgs->value instanceof Node\Expr\Variable) {
                        array_push($this->sqlVars, $queryArgs->value->name);
                    }
                }
            }
        }

        if (in_array(get_class($node), $this->sinks))   // version 4: node down, general, using recursive source search
    {
        if (get_class($node)=="PhpParser\Node\Expr\FuncCall")        // further check for Expr_FuncCall sinks
        {
            if (!is_array($node->name->parts))
            {
                return;
            }
            $func_name = array_values($node->name->parts)[0];
            if ($func_name!= "printf" && $func_name!= "print_r" && $func_name!= "var_dump") {
                return;
            }
        }
        if (!empty($this->findSourceRecursively($node)))
        {
            array_push($this->linesXSS,$node->getLine());
            $this->isVulnXSS = true;
                // echo implode(",",$this->linesXSS) . "\n";
        }
    }
    }


    public function findSourceRecursively(Node $node) {
        // echo get_class($node) ."\n";
        $subnode_names = $node->getSubNodeNames();
        if (empty($subnode_names)) {
            return NULL;
        }
        foreach ($subnode_names as $subnode_name) {
            if (in_array($node->$subnode_name, $this->sources)) {
                // echo get_class($node) . "\n";
                return $node;       // FOUND IT
            }
        }
        foreach ($subnode_names as $subnode_name)
        {
            $subnode = & $node->$subnode_name;
            // var_dump($subnode);
            if (is_array($subnode)) {
                foreach ($subnode as $subsubnode) {
                    if (!($subsubnode instanceof Node)) {
                        continue;
                    }
                    $return_node = $this->findSourceRecursively($subsubnode);
                    if (!empty($return_node)) {
                        return $return_node;     // return_node is node with subnode that matches a sink
                    }
                }
            }
            elseif ($subnode instanceof Node) {
                $return_node = $this->findSourceRecursively($subnode);
            }
            else {

                return null;
            }
            if (!empty($return_node)){
                return $return_node;    // return_node is node with subnode that matches a sink
            }
        }
        if (!empty($return_node)){
            return $return_node;    // return_node is node with subnode that matches a sink
        } // NULL

    }

    // Pops the node
    public function leaveNode(Node $node) {
        array_pop($this->stack);
    }

}

// // Gets the source code of all PHP files in a directory
// function getDirContents($dir, &$results = array()) {
//     $files = scandir($dir);

//     foreach ($files as $key => $value) {
//         $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
//         $characterCount = strlen ( $path );
//         if (!is_dir($path)) {
//             if(substr($path, $characterCount-3, $characterCount) == "php"){
//                 $results[] = $path;
//             }
//         } else if ($value != "." && $value != "..") {
//             getDirContents($path, $results);
//             if(substr($path, $characterCount-3, $characterCount) == "php"){
//                 $results[] = $path;
//             }
//         }
//     }

//     return $results;
// }

// // Runs vulnerability scanner
// function parser($directory){
//     $all_php_paths = getDirContents($directory);

//     foreach ($all_php_paths as $key => $value) {
//         // extending NodeVisitorAbstract to store the parent class
//         echo "\n" . "Scanning file " . str_replace(getcwd().'/', "", $value) . "\n";
//         $links_contents = file_get_contents($value);
//         $code = $links_contents;

//         // obtain AST
//         $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
//         try {
//             $ast = $parser->parse($code);
//         } catch (Error $error) {
//             echo "Parse error: {$error->getMessage()}\n";
//             return;
//         }

//         $traverser1 = new NodeTraverser();
//         $traverser2 = new NodeTraverser();

//         // traverse AST to find sqlVars
//         $vuln1 = new SQLVulnScan();
//         $traverser1->addVisitor ($vuln1);
//         $ast1 = $traverser1->traverse($ast);
//         $sqlVarArray = $vuln1->sqlVars;
        
//         //traverse AST to find sql statements
//         $vuln2 = new SQLVulnScan($sqlVarArray);
//         $traverser2->addVisitor ($vuln2);
//         $ast2 = $traverser2->traverse($ast);
//         $statements = $vuln2->sqlStatements;
//         $isVuln = $vuln2->isVuln;

//         if($isVuln){
//             for ($i = 0; $i < sizeof($statements); $i++) {
//                 echo "\tWARNING, Concatenating SQL statement detected, Possible SQL Injection\n";
//                 echo "\tFound in line ".$statements[$i]." of ".explode("/", $value)[sizeof(explode("/", $value))-1]."\n";
//             }
//         }
        

//     }

// }


// parser($argv[1]);

// // in one terminal python plugin_api.py 
// // in another tail -f /tmp/testfolder/vuln.txt








