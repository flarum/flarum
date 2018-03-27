<?php

namespace Studio\Creator;

use Studio\Shell\Shell;

class GitSubmoduleCreator extends GitRepoCreator
{
    protected function cloneRepository()
    {
        Shell::run("git submodule add $this->repo $this->path");
        Shell::run("git submodule init");
    }
}
