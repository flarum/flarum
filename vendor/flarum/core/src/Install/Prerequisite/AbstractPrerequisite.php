<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Prerequisite;

abstract class AbstractPrerequisite implements PrerequisiteInterface
{
    protected $errors = [];

    abstract public function check();

    public function getErrors()
    {
        return $this->errors;
    }
}
