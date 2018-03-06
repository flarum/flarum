<?php

namespace Studio;

use InvalidArgumentException;

class Package
{
    protected $vendor;

    protected $name;

    protected $path;


    public static function fromFolder($path)
    {
        $composerFile = "$path/composer.json";

        if (!file_exists($composerFile)) {
            throw new InvalidArgumentException("Unable to detect Composer package in $path.");
        }

        $composer = json_decode(file_get_contents($composerFile));

        if (!$composer->name) {
            throw new InvalidArgumentException("Unable to load package name from $path/composer.json.");
        }

        list($vendor, $name) = explode('/', $composer->name, 2);

        return new static($vendor, $name, $path);
    }

    public function __construct($vendor, $name, $path)
    {
        $this->vendor = $vendor;
        $this->name = $name;
        $this->path = $path;
    }

    public function getComposerId()
    {
        return $this->vendor . '/' . $this->name;
    }

    public function getVendor()
    {
        return $this->vendor;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPath()
    {
        return $this->path;
    }
}
