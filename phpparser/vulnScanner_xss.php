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

            public function beforeTraverse(array $nodes) {
                $this->stack = [];
                $this->sources = array("_REQUEST", "_GET", "_POST", "_SERVER");    // fill in
                $this->sinks = array("Node\Stmt\Echo_");    // fill in
            }

            public function enterNode(Node $node) {
                // $dumper = new NodeDumper;
                // echo $dumper->dump($node) . "\n\n\n";

                if (!empty($this->stack)) {
                    $node->setAttribute('parent', $this->stack[count($this->stack)-1]);
                    $node->setAttribute('grandparent', $this->stack[count($this->stack)-2]);
                    $node->setAttribute('grandgrandparent', $this->stack[count($this->stack)-3]);
                }
                // var_dump($node->getAttribute('parent'));
                // var_dump($node->getAttribute('grandparent'));
                // var_dump($node->getAttribute('grandgrandparent'));
                $this->stack[] = $node;
                // echo $node->name."\n";
                if ($node instanceof Node\Identifier && $node->name == "query") {
                    $queryName = $node->name;
                    $queryArgs = $node->getAttribute('parent')->args;
                    $varName = array_values($queryArgs)[0]->value->name;
                    echo $varName . "\n";
                }
                if ($node instanceof Node\Stmt\Echo_ && array_values($node->exprs)[0]->var->name == "_REQUEST") {    // version 1: node down approach, for echo and REQUEST, WORKING
                    $varName = array_values($node->exprs)[0]->dim->value;
                    echo $varName . "\n";
                }
                // if ($node instanceof Node\Stmt\Echo_ && array_values($node->exprs)[0]->var->name == "_REQUEST") {    // version 2: node down approach, for echo and REQUEST, using getSubNodeName
                //     $testarray = $node->getSubNodeNames() . "\n";
                //     var_dump($testarray);
                //     // for ($i = 0; $i < sizeof($testarray); $i++) {
                //     //     echo $testarray[i] . "\n";
                //     // }
                //     $varName = array_values($node->exprs)[0]->dim->value;
                //     echo $varName . "\n";
                // }

                // if ($node instanceof Node\Expr\Variable && in_array($node->name, $this->sources)) {        // version 3: node up approach, general
                //
                //     foreach ($this->sinks as $sink)
                //     {
                //
                //         // need to clear $sinknode
                //         if (get_class($node->getAttribute('parent')) == $sink){
                //             echo "parent\n";
                //             $sinknode = $node->getAttribute('parent');
                //         }
                //         elseif (get_class($node->getAttribute('grandparent')) == $sink) {
                //             echo "grandparent\n";
                //             $sinknode = $node->getAttribute('grandparent');
                //         }
                //         elseif (get_class($node->getAttribute('grandgrandparent')) == $sink) {
                //             echo "grandgrandparent\n";
                //             $sinknode = $node->getAttribute('grandgrandparent');
                //         }
                //
                //         if (!empty($sinknode)) {
                //             echo "sink found\n";
                //             break;
                //         }
                //     }
                //     if (!empty($sinknode))
                //     {
                //         if ($node->name != "_SERVER") {
                //             $varName = $node->getAttribute('parent')->dim->value;
                //         }
                //         else {
                //             $varName = $node->array_values(getAttribute('parent')->dim->name->parts)[0];
                //         }
                //         echo $varName . "\n";
                //     }
                //     // $queryName = $node->name;
                //     // $echoArgs = $node->getAttribute('parent')->args;
                //
                // }

            }

            public function leaveNode(Node $node) {
                array_pop($this->stack);
            }

        });

        // traverse AST
        $dumper = new NodeDumper;
        echo "\n\n\n\n\n\n\n\n\nAST FOR " . $value . "\n" . $dumper->dump($ast) . "\n";
        $ast = $traverser->traverse($ast);
	    // echo "\n\n\n\n\n\n\n\n\nAST FOR " . $value . "\n" . $dumper->dump($ast) . "\n";
    }

}

parser($argv[1])
?>
