<?php

use PHPUnit\Framework\TestCase;
use VarDumper\Dumper;

/**
 * Var Dumper Test
 */
class DumperTest extends TestCase
{
    /**
     * ModelTest TearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        // remove the dummy sqlite database;
        if (file_exists(__DIR__ . '/../memory')) {
            unlink(__DIR__ . '/../memory');
        }

        // clear output files
        file_put_contents(__DIR__ . "/output/file.txt", "");
        file_put_contents(__DIR__ . "/output/file.json", "");
    }

    /**
     * Select output type and print the value according to.
     * 
     * @param string $outputType
     * @return object
     */
    private function getDumpFunction($outputType)
    {
        $output = '';
        $file = '';

        switch ($outputType) {
            case 'cli':
                $output = Dumper::VAR_DUMPER_OUTPUT_CLI;
                break;
            case 'web':
                $output = Dumper::VAR_DUMPER_OUTPUT_WEB;
                break;
            case 'file':
            case 'json':
                $output = Dumper::VAR_DUMPER_OUTPUT_FILE;
                $file = __DIR__ . "/output/file." . ($outputType == 'json' ?
                    'json' : 'txt');
                break;
        }

        return function (...$variables) use ($output, $file) {
            Dumper::dump($output, $variables, debug_backtrace(), $file);
        };
    }

    /**
     * Loop throw set of lines and check if all are matches.
     * 
     * @param string $results
     * @param string $validResults
     * @return bool
     */
    private function checkResults($results, $validResults)
    {
        $validResults = explode("\n", $validResults);
        $results = explode("\n", $results);

        foreach($results as $i => $line) {
            // skip all variable values , since these values
            // will always change
            if (strpos($line, 'DumperTest.php') !== false ||
                strpos($line, 'cli.txt') !== false ||
                strpos($line, 'file =>') !== false ||
                strpos($line, 'dump_') !== false ||
                strpos($line, 'time') !== false ||
                strpos($line, 'line') !== false ||
                empty(trim($line))
            ) {
                continue;
            }

            // skip zend auto generated ids
            $results[$i] = preg_replace(
                '~\#([0-9]*)~', 'N', $results[$i]
            );

            $validResults[$i] = preg_replace(
                '~\#([0-9]*)~', 'N', $validResults[$i]
            );

            if (trim($results[$i]) != trim($validResults[$i])) {
                var_dump($i, trim($results[$i]), trim($validResults[$i]));
                return false;
            }
        }

        return true;
    }

    /**
     * Run dumper method on a set of data.
     *
     * @param object $dumper
     * @return void
     */
    private function runDumper($dumper)
    {
        // basic
        $hello = "world";

        $dumper('ahmed', 5.6, false, $hello, NULL);
        $dumper("/^([A-Za-z1-9])$/");
        $dumper(serialize([1, 2, 3]));
        $dumper("http://www.helloworld.com");
        $dumper("test@example.com");
        $dumper('20-5-1995');
        $dumper(fopen(__DIR__ . '/results/cli.txt', 'r'));

        // arrays
        $dumper(array(1, 2, 3));

        // objects
        $dumper(new \StdClass());

        $d = DateTime::createFromFormat(
            'Y-m-d h:i:s',
            '1995-5-20 12:28:14'
        );

        $dumper($d);

        $bar = new Bar();
        $foo = new Foo();

        $bar->arr = [
            'a' => 'apple',
            'b' => 'banana',
            'c' => [1, 2, [1, 2, 3]],
            'd' => 'dates',
            'ahmed' => []
        ];

        $foo->anonymous = new class
        {
            public $name;

            public function __construct()
            {
                $this->name = 'ahmed';
            }
        };

        $foo->bar = $bar;

        $dumper($foo);

        // test anonymous class
        $dumper(
            new class
            {
                public \PDO $conn;

                public function __construct()
                {
                    $this->conn = new \PDO('sqlite:memory');
                }
            }
        );
        
        $dumper(
            new class
            {
                private $conn;

                public function __construct()
                {
                    $this->conn = new \PDO('sqlite:memory');
                }
            }
        );

        // test closures
        $closure = function ($name, $age = '') {
            return $name;
        };

        $dumper($closure);
        $dumper(function () {});

        // test enum
        $dumper((Week::Saturday));

        // test array
        $dumper([
            'a' => 'apple',
            'b' => 'banana',
            'c' => [1, 2, [1, 2, 3]],
            'd' => 'dates',
            'arr' => []
        ]);

        // test long text
        $dumper(file_get_contents(__DIR__ . '/results/long.txt'));
    }

    /**
     * Test dump data to CLI output.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testDumpCli()
    {
        ob_start();

        $this->runDumper($this->getDumpFunction('cli'));

        $this->assertTrue(
            $this->checkResults(
                ob_get_clean(), 
                file_get_contents(__DIR__ . "/results/cli.txt")
            )
        );
    }

    /**
     * Test dump data to Web output.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testDumpWeb()
    {
        ob_start();

        $this->runDumper($this->getDumpFunction('web'));

        $this->assertTrue(
            $this->checkResults(
                ob_get_clean(), 
                file_get_contents(__DIR__ . "/results/web.html")
            )
        );
    }

    /**
     * Test dump data to a file.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testDumpFile()
    {
        $this->runDumper($this->getDumpFunction('file'));

        $this->assertTrue(
            $this->checkResults(
                file_get_contents(__DIR__ . "/output/file.txt"),
                file_get_contents(__DIR__ . "/results/file.txt")
            )
        );
    }

    /**
     * Test dump data to a json file.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testDumpJsonFile()
    {
        $this->runDumper($this->getDumpFunction('json'));

        $this->assertTrue(
            $this->checkResults(
                file_get_contents(__DIR__ . "/output/file.json"),
                file_get_contents(__DIR__ . "/results/file.json")
            )
        );
    }

    /**
     * Test dumper will throw exception if output type is invalid.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testDumperWillThrowExceptionIfOutputTypeIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);

        Dumper::dump('', '', '');
    }

    /**
     * Test dumper will throw exception if no variables were provided.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testDumperWillThrowExceptionIfNoVariablesWereProvided()
    {
        $this->expectException(\InvalidArgumentException::class);

        d();
    }

    /**
     * Test dumper will throw exception if the dump file is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testDumperWillThrowExceptionIfTheDumpFileIsNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);

        dump_to_file("unknown_file");
    }
    
    /**
     * Test complex cases.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testComplexCases()
    {
        ob_start();

        $foo1 = new class {
            private \PDO $bar;
        };
        
        $foo2 = new class {
            private $bar;
        };
        
        $foo3 = new class {
            private $bar = 5;
        };
        
        $foo4 = new class {
            private int $bar;
        };
        
        $foo5 = new class {
            private int $bar = 5;
        };
        
        $foo6 = new class {
            public \PDO $bar;
        };
        
        $foo7 = new class {
            public $bar;
        };
        
        $foo8 = new class {
            public $bar = 5;
        };
        
        $foo9 = new class {
            public int $bar;
        };
        
        $foo10 = new class {
            public int $bar = 5;
        };

        d($foo1);
        d($foo2);
        d($foo3);
        d($foo4);
        d($foo5);
        d($foo6);
        d($foo7);
        d($foo8);
        d($foo9);
        d($foo10);

        $this->assertTrue(
            $this->checkResults(
                ob_get_clean(), 
                file_get_contents(__DIR__ . "/results/complex.txt")
            )
        );
    }
}

/**
 * Testing Classes
 */
class Foo
{
    public $name;
    private int $age = 555;
    public $anonymous;
    private array $arr = [];
    public Bar $bar;
    public static $phone;
    const country = '123';
    private $conn;

    public function __construct()
    {
        $this->conn = new \PDO('sqlite:memory');
    }

    public function sayHallo($a)
    {
        return 123;
    }
}

class Bar
{
    public $arr;
}

enum Week
{
    case Saturday;
    case Sunday;
    case Monday;
    case Tuesday;
    case Wednesday;
    case Thursday;
    case Friday;
}