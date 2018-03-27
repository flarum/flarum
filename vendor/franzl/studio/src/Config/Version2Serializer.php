<?php

namespace Studio\Config;

class Version2Serializer implements Serializer
{
    public function deserializePaths($obj)
    {
        return $obj['paths'];
    }

    public function serializePaths(array $paths)
    {
        sort($paths);
        return ['paths' => array_values($paths)];
    }
}
