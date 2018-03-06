<?php

namespace Studio\Parts;

use Studio\Filesystem\Directory;

interface PartInterface
{
    public function setupPackage($composer, Directory $target);
}
