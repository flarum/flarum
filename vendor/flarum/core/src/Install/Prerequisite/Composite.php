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

class Composite implements PrerequisiteInterface
{
    /**
     * @var PrerequisiteInterface[]
     */
    protected $prerequisites = [];

    public function __construct(PrerequisiteInterface $first)
    {
        foreach (func_get_args() as $prerequisite) {
            $this->prerequisites[] = $prerequisite;
        }
    }

    public function check()
    {
        return array_reduce(
            $this->prerequisites,
            function ($previous, PrerequisiteInterface $prerequisite) {
                return $prerequisite->check() && $previous;
            },
            true
        );
    }

    public function getErrors()
    {
        return collect($this->prerequisites)->map(function (PrerequisiteInterface $prerequisite) {
            return $prerequisite->getErrors();
        })->reduce('array_merge', []);
    }
}
