<?php

namespace Studio\Creator;

interface CreatorInterface
{
    /**
     * Create the new package.
     *
     * @return \Studio\Package
     */
    public function create();
}
