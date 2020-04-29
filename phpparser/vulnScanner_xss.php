<?php
 
require 'vendor/autoload.php';
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeFinder;
 
function getDirContents($dir, &$results = array()) {
    $files = scandir($dir);
 
    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        $characterCount = strlen ( $path );
        if (!is_dir($path)) {
            if(substr($path, -3) == "php" && basename($path, ".php")!="index"){
                $results[] = $path;
            }
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            if(substr($path, -3) == "php" && basename($path, ".php")!="index"){
                $results[] = $path;
            }
        }
    }
 
    return $results;
}
 
class ParentConnector extends NodeVisitorAbstract {
    private $stack;
    public function beforeTraverse(array $nodes) {
        $this->stack = [];
    }
    public function enterNode(Node $node) {
        if (!empty($this->stack)) {
            echo "test";
            $node->setAttribute('parent', $this->stack[count($this->stack)-1]);
        }
        $this->stack[] = $node;
    }
    public function leaveNode(Node $node) {
        array_pop($this->stack);
    }
}
 
function parser($directory){
    $all_php_paths = getDirContents($directory);
 
    foreach ($all_php_paths as $key => $value) {
        // extending NodeVisitorAbstract to store the parent class
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
 
    // begin traverser
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class extends NodeVisitorAbstract {
 
            private $stack;
            public $varName;
            public $sinks;
            public $sources;
            public $lines;
<<<<<<< HEAD
 
=======

>>>>>>> ed62b72cb26d81c1bc810f84fdcfeaca7e42532a
            public function beforeTraverse(array $nodes) {
                $this->stack = [];
                $this->sources = array("_REQUEST", "_GET", "_POST", "_SERVER");    // fill in
                $this->sinks = array("PhpParser\Node\Stmt\Echo_","PhpParser\Node\Expr\Exit_", "PhpParser\Node\Expr\Print_", "PhpParser\Node\Expr\FuncCall");    // fill in
                $this->lines=[];
            }
 
            public function enterNode(Node $node) {
<<<<<<< HEAD
 
=======

>>>>>>> ed62b72cb26d81c1bc810f84fdcfeaca7e42532a
                if (!empty($this->stack)) {
                    $node->setAttribute('parent', $this->stack[count($this->stack)-1]);
                    $node->setAttribute('grandparent', $this->stack[count($this->stack)-2]);
                    $node->setAttribute('grandgrandparent', $this->stack[count($this->stack)-3]);
                }
<<<<<<< HEAD
 
                $this->stack[] = $node;
 
=======

                $this->stack[] = $node;

>>>>>>> ed62b72cb26d81c1bc810f84fdcfeaca7e42532a
                if ($node instanceof Node\Identifier && $node->name == "query") {
                    $queryName = $node->name;
                    $queryArgs = $node->getAttribute('parent')->args;
                    $varName = array_values($queryArgs)[0]->value->name;
                    echo $varName . "\n";
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
<<<<<<< HEAD
 
=======

>>>>>>> ed62b72cb26d81c1bc810f84fdcfeaca7e42532a
            }
 
            public function leaveNode(Node $node) {
                array_pop($this->stack);
            }
<<<<<<< HEAD
 
=======

>>>>>>> ed62b72cb26d81c1bc810f84fdcfeaca7e42532a
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
<<<<<<< HEAD
 
=======

>>>>>>> ed62b72cb26d81c1bc810f84fdcfeaca7e42532a
                        echo "Error in recursively traversing\n";
                    }
                    if (!empty($return_node)){
                        return $return_node;    // return_node is node with subnode that matches a sink
                    }
                }
                return $return_node; // NULL
<<<<<<< HEAD
 
            }
 
=======

            }

>>>>>>> ed62b72cb26d81c1bc810f84fdcfeaca7e42532a
        });
 
        // traverse AST
        $dumper = new NodeDumper;
        // echo "\n\n\n\n\n\n\n\n\nAST FOR " . $value . "\n" . $dumper->dump($ast) . "\n";
        $ast = $traverser->traverse($ast);
        // echo "\n\n\n\n\n\n\n\n\nAST FOR " . $value . "\n" . $dumper->dump($ast) . "\n";
    }
 
}
 
parser($argv[1])
?>