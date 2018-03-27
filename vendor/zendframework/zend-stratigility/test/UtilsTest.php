<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Stratigility;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Stratigility\Dispatch;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\Utils;
use ZendTest\Stratigility\TestAsset\NormalHandler;
use ZendTest\Stratigility\TestAsset\StaticHandler;

class UtilsTest extends TestCase
{
    public function callablesWithVaryingArity()
    {
        return [
            'function'       => ['strlen', 1],
            'closure'        => [function ($x, $y) {
            }, 2],
            'invokable'      => [new Dispatch(), 5],
            'interface'      => [new MiddlewarePipe(), 2], // 2 REQUIRED arguments!
            'callable'       => [[new NormalHandler(), 'handle'], 3],
            'static-method'  => [[__NAMESPACE__ . '\TestAsset\StaticHandler', 'handle'], 3],
            'static-access'  => [__NAMESPACE__ . '\TestAsset\StaticHandler::handle', 3],
        ];
    }

    /**
     * @dataProvider callablesWithVaryingArity
     */
    public function testArity($callable, $expected)
    {
        $this->assertEquals($expected, Utils::getArity($callable));
    }

    public function nonCallables()
    {
        return [
            'null'                => [null],
            'false'               => [false],
            'true'                => [true],
            'int'                 => [1],
            'float'               => [1.1],
            'string'              => ['not a callable'],
            'array'               => [['not a callable']],
            'non-callable-object' => [(object) ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider nonCallables
     */
    public function testReturnsZeroForNonCallableArguments($test)
    {
        $this->assertSame(0, Utils::getArity($test));
    }
}
