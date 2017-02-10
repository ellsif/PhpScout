<?php
namespace ellsif\PhpScout\Tests;

use ellsif\PhpScout\Scout;
use PHPUnit\Framework\TestCase;

class ScoutTest extends TestCase
{
    public static $resultTestClass;
    public static $resultTestAbstract;
    public static $resultTestInterface;
    public static $resultTestTrait;

    public static function setUpBeforeClass()
    {
        $scout = new Scout();
        ScoutTest::$resultTestClass = $scout->scout(dirname(__FILE__, 3) . '/data/testClass.php');
        ScoutTest::$resultTestAbstract = $scout->scout(dirname(__FILE__, 3) . '/data/testAbstract.php');
        ScoutTest::$resultTestInterface = $scout->scout(dirname(__FILE__, 3) . '/data/testInterface.php');
        ScoutTest::$resultTestTrait = $scout->scout(dirname(__FILE__, 3) . '/data/testTrait.php');
    }

    public function testNameSpace()
    {
        $result = ScoutTest::$resultTestClass;
        $this->assertEquals('Vendor\Package', $result['namespace']);
    }

    public function testClass()
    {
        $result = ScoutTest::$resultTestClass;
        $this->assertArrayHasKey('class', $result);
        $this->assertEquals('Foo', $result['class']['name']);
        $this->assertEquals('class Foo extends Bar implements FooInterface', $result['class']['define']);
        $this->assertEquals(
            'Class Foo

## Example
    $this->is(\'test data\');', $result['class']['comment']);
    }

    public function testAbstractClass()
    {
        $result = ScoutTest::$resultTestClass;
        $this->assertFalse($result['class']['abstract']);

        $result = ScoutTest::$resultTestAbstract;
        $this->assertTrue($result['class']['abstract']);
    }

    public function testInterface()
    {
        $result = ScoutTest::$resultTestInterface;
        $this->assertArrayHasKey('interface', $result);
        $this->assertEquals('DataAccess', $result['interface']['name']);
        $this->assertEquals('interface DataAccess', $result['interface']['define']);
    }

    public function testTrait()
    {
        $result = ScoutTest::$resultTestTrait;
        $this->assertArrayHasKey('trait', $result);
        $this->assertEquals('Singleton', $result['trait']['name']);
        $this->assertEquals('trait Singleton', $result['trait']['define']);
    }

    public function testFunction()
    {
        $result = ScoutTest::$resultTestClass;
        $this->assertArrayHasKey('functions', $result);
        $this->assertCount(2, $result['functions']);
    }

}
