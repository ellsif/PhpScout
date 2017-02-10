<?php
namespace Vendor\Package;

use FooInterface;
use BarClass as Bar;
use OtherVendor\OtherPackage\BazClass;

/**
 * Class Foo
 *
 * ## Example
 *     $this->is('test data');
 */
class Foo extends Bar implements FooInterface
{
    const CONSTANT = 'test constant';
    const CONSTANTS = [ 'test constant one', 'test constant two' ];

    private $prop = null;
    public $param;

    /**
     * this is sample
     *
     * ##
     * this is test
     *
     * - test 1
     * - test 2
     * - test 3
     */
    public function sampleMethod($a, $b = null)
    {
        if ($a === $b) {
            bar();
        } elseif ($a > $b) {
            $foo->bar($arg1);
        } else {
            BazClass::bar($arg2, $arg3);
        }
    }

    // this is line comment
    final public static function bar()
    {
        // method body
    }
}
