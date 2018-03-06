<?php

namespace Studio\Filesystem;

use Symfony\Component\Filesystem\Filesystem;

class Directory
{
    protected $path;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function write($file, $contents)
    {
        $path = $this->getPath($file);
        $this->getFilesystem()->dumpFile($path, $contents);
    }

    public function makeDir($name)
    {
        $path = $this->getPath($name);
        $this->getFilesystem()->mkdir($path);
    }

    protected function getFilesystem()
    {
        if (!$this->filesystem) {
            $this->filesystem = new Filesystem;
        }

        return $this->filesystem;
    }

    protected function getPath($file)
    {
        return $this->path . "/$file";
    }
}
