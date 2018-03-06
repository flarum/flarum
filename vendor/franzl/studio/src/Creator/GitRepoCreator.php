<?php

namespace Studio\Creator;

use Studio\Package;
use Studio\Shell\Shell;

class GitRepoCreator implements CreatorInterface
{
    protected $repo;

    protected $path;


    public function __construct($repo, $path)
    {
        $this->repo = $repo;
        $this->path = $path;
    }

    /**
     * Create the new package.
     *
     * @return \Studio\Package
     */
    public function create()
    {
        $this->cloneRepository();

        return Package::fromFolder($this->path);
    }

    protected function cloneRepository()
    {
        Shell::run("git clone $this->repo $this->path");
    }
}
