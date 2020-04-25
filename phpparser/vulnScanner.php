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

// test code ###########################################################
$code = <<<'CODE'
<?php
if (PHP_SAPI === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

$file_db = new PDO('sqlite:../database/database.sqlite');

if (NULL == $_GET['id']) $_GET['id'] = 1;

$sql = 'SELECT * FROM employees WHERE employeeId = ' . $_GET['id'];

foreach ($file_db->query($sql) as $row) {
    $employee = $row['LastName'] . " - " . $row['Email'] . "\n";

    echo $employee;
}
?>
CODE;
// #####################################################################

// extending NodeVisitorAbstract to store the parent class
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
// echo $dumper->dump($ast) . "\n";


