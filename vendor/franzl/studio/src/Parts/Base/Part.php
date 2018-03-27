<?php

namespace Studio\Parts\Base;

use Studio\Filesystem\Directory;
use Studio\Parts\AbstractPart;

class Part extends AbstractPart
{
    public function setupPackage($composer, Directory $target)
    {
        $target->makeDir('src');
        $target->makeDir('tests');

        $this->copyTo(__DIR__ . '/stubs/gitignore.txt', $target, '.gitignore');
    }
}
