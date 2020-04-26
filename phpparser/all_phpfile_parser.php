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
            public function beforeTraverse(array $nodes) {
                $this->stack = [];
            }

            public function enterNode(Node $node) {
                // $dumper = new NodeDumper;
                // echo $dumper->dump($node) . "\n\n\n";

                if (!empty($this->stack)) {
                    $node->setAttribute('parent', $this->stack[count($this->stack)-1]);
                }
                $this->stack[] = $node;

                if ($node instanceof Node\Identifier && $node->name == "query") {
                    $queryName = $node->name;
                    $queryArgs = $node->getAttribute('parent')->args;
                    $varName = array_values($queryArgs)[0]->value->name;
                    echo $varName . "\n";
                }
            }

            public function leaveNode(Node $node) {
                array_pop($this->stack);
            }

        });

        // traverse AST
        $dumper = new NodeDumper;
        $ast = $traverser->traverse($ast);
    }

}

parser($argv[1])
?>