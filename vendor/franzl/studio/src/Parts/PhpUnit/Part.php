<?php

namespace Studio\Parts\PhpUnit;

use Studio\Filesystem\Directory;
use Studio\Parts\AbstractPart;

class Part extends AbstractPart
{
    public function setupPackage($composer, Directory $target)
    {
        if ($this->input->confirm('Do you want to set up PhpUnit as a testing tool?')) {
            $composer->{'require-dev'}['phpunit/phpunit'] = '4.*';

            // Add autoloading rules for tests
            $psr4Autoloading = (array) $composer->autoload->{'psr-4'};
            $namespace = key($psr4Autoloading).'Tests';

            @$composer->{'autoload-dev'}->{'psr-4'}->{"$namespace\\"} = 'tests/';

            // Create an example test file
            $this->copyTo(
                __DIR__ . '/stubs/tests/ExampleTest.php',
                $target,
                'tests/ExampleTest.php',
                function ($content) use ($namespace) {
                    return preg_replace('/namespace[^;]+;/', "namespace $namespace;", $content);
                }
            );

            $this->copyTo(__DIR__ . '/stubs/phpunit.xml', $target);
        }
    }
}
