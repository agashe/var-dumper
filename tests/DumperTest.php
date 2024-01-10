<?php

use PHPUnit\Framework\TestCase;
use VarDumper\Dumper;

/**
 * Var Dumper Test
 */
class DumperTest extends TestCase
{
    /**
     * Loop throw set of lines and check if all are matches.
     * 
     * @param string $results
     * @return bool
     */
    private function checkResults($results)
    {
        $validResults = explode("\n", 
            file_get_contents(__DIR__ . '/results.txt'));

        $results = explode("\n", $results);

        foreach($results as $i => $line) {
            if (strpos($line, 'DumperTest.php') !== false ||
                strpos($line, 'results.txt') !== false ||
                strpos($line, 'file =>') !== false ||
                empty(trim($line))
            ) {
                continue;
            }

            $results[$i] = preg_replace(
                '~\#([0-9]*) \=\> \{~', 'N', $results[$i]
            );

            $validResults[$i] = preg_replace(
                '~\#([0-9]*) \=\> \{~', 'N', $validResults[$i]
            );

            $results[$i] = preg_replace(
                '~\#([0-9]*) \(.*?\) \=\> \{~', 'N', $results[$i]
            );

            $validResults[$i] = preg_replace(
                '~\#([0-9]*) \(.*?\) \=\> \{~', 'N', $validResults[$i]
            );

            if (trim($results[$i]) != trim($validResults[$i])) {
                // print $i . PHP_EOL;
                // print trim($results[$i]) . PHP_EOL;
                // print trim($validResults[$i]) . PHP_EOL;
                return false;
            }
        }

        return true;
    }

    /**
     * Test dump data.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testDump()
    {
        ob_start();

        // basic
        $hello = "world";

        dump('ahmed', 5.6, false, $hello, NULL);
        dump("/^([A-Za-z1-9])$/");
        dump(serialize([1, 2, 3]));
        dump("http://www.helloworld.com");
        dump("test@example.com");
        dump('20-5-1995');
        dump(fopen(__DIR__ . '/results.txt', 'r'));

        // arrays
        dump(array(1, 2, 3));

        // objects
        dump(new \StdClass());

        $d = DateTime::createFromFormat('Y-m-d h:i:s', '1995-5-20 12:28:14');
        dump($d);

        $bar = new Bar();
        $foo = new Foo();

        $bar->arr = [
            'a' => 'apple',
            'b' => 'banana',
            'c' => [1, 2, [1, 2, 3]],
            'd' => 'dates',
            'ahmed' => []
        ];

        $foo->arr = new class
        {
            public $name;

            public function __construct()
            {
                $this->name = 'ahmed';
            }
        };

        $foo->bar = $bar;

        dump($foo);

        // test anonymous class
        dump(
            new class
            {
                public $name;

                public function __construct()
                {
                    $this->name = 'ahmed';
                }
            }
        );

        // test closures
        $closure = function ($name, $age = '') {
            return $name;
        };

        dump($closure);
        dump(function () {});

        // test enum
        dump((Week::Saturday));

        // test array
        dump([
            'a' => 'apple',
            'b' => 'banana',
            'c' => [1, 2, [1, 2, 3]],
            'd' => 'dates',
            'arr' => []
        ]);

        // test long text
        // THIS LINE IS EXCEPTION FOR THE 80 CHARACTERS RULE :(
        dump("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris viverra at est sed vestibulum. Ut ultricies urna et bibendum sollicitudin. Maecenas suscipit bibendum ante convallis dignissim. Nulla facilisi. Sed mollis eget purus eget finibus.Donec convallis risus sit amet dapibus vehicula. Interdum et malesuada fames ac ante ipsum primis in faucibus. Vestibulum vel aliquet ante. Suspendisse vitae neque non nulla viverra blandit at at diam. Phasellus imperdiet quis lacus sed facilisis. Mauris lacinia arcu lorem, quis commodo arcu fringilla in. ");

        $this->assertTrue(
            $this->checkResults(ob_get_clean())
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

        \VarDumper\Dumper::dump('', '', '');
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
}

/**
 * Testing Classes
 */
class Foo
{
    public $name;
    private $age;
    public $arr;
    public $bar;
    public static $phone;
    const country = '123';

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