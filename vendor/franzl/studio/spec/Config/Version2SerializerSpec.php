<?php

namespace spec\Studio\Config;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class Version2SerializerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Studio\Config\Version2Serializer');
    }

    function it_stores_paths_alphabetically()
    {
        $this->serializePaths(['foo', 'bar'])->shouldReturn(['paths' => ['bar', 'foo']]);
    }
}
