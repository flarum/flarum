<?php

namespace Studio\Config;

use Studio\Package;

class Version1Serializer implements Serializer
{
    public function deserializePaths($obj)
    {
        return array_values($obj['packages']);
    }

    public function serializePaths(array $paths)
    {
        $globbedPaths = array_map(function ($path) {
            return glob($path, GLOB_MARK | GLOB_ONLYDIR);
        }, $paths);

        $allPaths = array_reduce($globbedPaths, function ($collect, $pathOrPaths) {
            if (is_array($pathOrPaths)) {
                return array_merge($collect, $pathOrPaths);
            } else {
                $collect[] = $pathOrPaths;
                return $collect;
            }
        }, []);

        $allPaths = array_filter($allPaths, function ($path) {
            return is_dir($path) && file_exists("$path/composer.json");
        });

        $packages = array_map(function ($path) {
            return Package::fromFolder(rtrim($path, '/'));
        }, $allPaths);

        $packagePaths = array_reduce($packages, function ($collect, Package $package) {
            $collect[$package->getComposerId()] = $package->getPath();
            return $collect;
        }, []);

        return ['packages' => $packagePaths];
    }
}
