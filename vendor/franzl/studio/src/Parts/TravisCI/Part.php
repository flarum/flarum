<?php

namespace Studio\Parts\TravisCI;

use Studio\Filesystem\Directory;
use Studio\Parts\AbstractPart;

class Part extends AbstractPart
{
    public function setupPackage($composer, Directory $target)
    {
        if ($this->input->confirm('Do you want to set up TravisCI as continuous integration tool?')) {
            $this->copyTo(__DIR__ . '/stubs/.travis.yml', $target);
        }
    }
}
