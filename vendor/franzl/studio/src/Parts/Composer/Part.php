<?php

namespace Studio\Parts\Composer;

use Studio\Filesystem\Directory;
use Studio\Parts\AbstractPart;

class Part extends AbstractPart
{
    public function setupPackage($composer, Directory $target)
    {
        // Ask for package name
        $composer->name = $this->input->ask(
            'Please name this package',
            '/[[:alnum:]]+\/[[:alnum:]]+/',
            'Please enter a valid package name in the format "vendor/name".'
        );

        // Ask for the root namespace
        $namespace = $this->input->ask(
            'Please provide a default namespace (PSR-4)',
            '/([[:alnum:]]+\\\\?)+/',
            'Please enter a valid PHP namespace',
            $this->makeDefaultNamespace($composer->name)
        );

        // Normalize and store the namespace
        $namespace = str_replace('/', '\\', $namespace);
        $namespace = rtrim($namespace, '\\');
        @$composer->autoload->{'psr-4'}->{"$namespace\\"} = 'src/';

        // Create an example file
        $this->copyTo(
            __DIR__ . '/stubs/src/Example.php',
            $target,
            'src/Example.php',
            function ($content) use ($namespace) {
                return preg_replace('/namespace[^;]+;/', "namespace $namespace;", $content);
            }
        );
    }

    protected function makeDefaultNamespace($package)
    {
        list($vendor, $name) = explode('/', $package);

        return ucfirst($vendor) . '\\' . ucfirst($name);
    }
}
