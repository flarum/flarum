<?php

namespace Studio\Config;

use Studio\Package;

class Config
{
    /**
     * @var Serializer
     */
    protected $serializer;

    protected $paths;

    protected $loaded = false;

    protected $file;


    public function __construct($file, Serializer $serializer)
    {
        $this->file = $file;
        $this->serializer = $serializer;
    }

    public static function make($file = null)
    {
        if (is_null($file)) {
            $file = getcwd().'/studio.json';
        }

        return new static(
            $file,
            VersionedSerializer
                ::withDefault(1, new Version1Serializer)
                ->version(2, new Version2Serializer)
        );
    }

    protected function readPaths()
    {
        if (!file_exists($this->file)) return [];

        $data = $this->readFromFile();
        return $this->serializer->deserializePaths($data);
    }

    public function getPaths()
    {
        if (! $this->loaded) {
            $this->paths = $this->readPaths();
            $this->loaded = true;
        }

        return $this->paths;
    }

    public function addPath($path)
    {
        // Ensure paths are loaded
        $this->getPaths();

        $this->paths[] = $path;
        $this->dump();
    }

    public function removePath($path)
    {
        // Ensure paths are loaded
        $this->getPaths();

        $this->paths = array_filter($this->paths, function ($existing) use ($path) {
            return $existing !== $path;
        });

        $this->dump();
    }

    public function hasPackages()
    {
        // Ensure paths are loaded
        $this->getPaths();

        return ! empty($this->paths);
    }

    public function removePackage(Package $package)
    {
        // Ensure paths are loaded
        $this->getPaths();

        $path = $package->getPath();

        if (($key = array_search($path, $this->paths)) !== false) {
            unset($this->paths[$key]);
            $this->dump();
        }
    }

    protected function dump()
    {
        $this->writeToFile(
            $this->serializer->serializePaths($this->paths)
        );
    }

    protected function writeToFile(array $data)
    {
        file_put_contents(
            $this->file,
            json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )."\n"
        );
    }

    protected function readFromFile()
    {
        return json_decode(file_get_contents($this->file), true);
    }
}
