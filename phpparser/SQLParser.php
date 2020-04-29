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
    public $lines;

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
                                array_push($this->lines, $node->getLine());
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

                $this->sqlVar = $varName;
                array_push($this->sqlVars, $varName);
            }
        }
        if (in_array(get_class($node), $this->sinks))   // version 4: node down, general, using recursive source search
        {
            if (get_class($node)=="PhpParser\Node\Expr\FuncCall")        // further check for Expr_FuncCall sinks
            {
                $func_name = array_values($node->name->parts)[0];
                if ($func_name!= "printf" && $func_name!= "print_r" && $func_name!= "var_dump") {
                    return;
                }
            }
            if (!empty($this->findSourceRecursively($node)))
            {
                array_push($this->lines,$node->getLine());
                echo implode(",",$this->lines) . "\n";
            }
        }
    }

    public function findSourceRecursively(Node $node) {
        echo get_class($node) ."\n";
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

                echo "Error in recursively traversing\n";
            }
            if (!empty($return_node)){
                return $return_node;    // return_node is node with subnode that matches a sink
            }
        }
        return $return_node; // NULL

    }

    // Pops the node
    public function leaveNode(Node $node) {
        array_pop($this->stack);
    }

}

?>
