<?php

/*
 * The RandomLib library for securely generating random numbers and strings in PHP
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */
namespace RandomLib;

use SecurityLib\Strength;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $factory = new Factory();
        $this->assertTrue($factory instanceof Factory);
    }

    public function testGetGeneratorFallback()
    {
        $factory = new Factory();
        $generator = $factory->getGenerator(new Strength(Strength::VERYLOW));
        $mixer = call_user_func(array(
            get_class($generator->getMixer()),
            'getStrength',
        ));
        $this->assertTrue($mixer->compare(new Strength(Strength::VERYLOW)) <= 0);
    }

    /**
     * @covers RandomLib\Factory::getMediumStrengthGenerator
     * @covers RandomLib\Factory::getGenerator
     * @covers RandomLib\Factory::findMixer
     * @covers RandomLib\Factory::findSources
     */
    public function testGetMediumStrengthGenerator()
    {
        $factory = new Factory();
        $generator = $factory->getMediumStrengthGenerator();
        $this->assertTrue($generator instanceof Generator);
        $mixer = call_user_func(array(
            get_class($generator->getMixer()),
            'getStrength',
        ));
        $this->assertTrue($mixer->compare(new Strength(Strength::MEDIUM)) <= 0);
        foreach ($generator->getSources() as $source) {
            $strength = call_user_func(array(get_class($source), 'getStrength'));
            $this->assertTrue($strength->compare(new Strength(Strength::MEDIUM)) >= 0);
        }
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not find sources
     */
    public function testNoAvailableSource()
    {
        $factory = new Factory();
        $sources = new \ReflectionProperty($factory, 'sources');
        $sources->setAccessible(true);
        $sources->setValue($factory, array());
        $factory->getMediumStrengthGenerator();
    }
}
