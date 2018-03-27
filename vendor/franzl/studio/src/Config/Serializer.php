<?php

namespace Studio\Config;

interface Serializer
{
    public function deserializePaths($obj);

    public function serializePaths(array $paths);
}
