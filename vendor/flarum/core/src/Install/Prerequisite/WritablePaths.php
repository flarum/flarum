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

class WritablePaths extends AbstractPrerequisite
{
    protected $paths;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function check()
    {
        foreach ($this->paths as $path) {
            if (! is_writable($path)) {
                $this->errors[] = [
                    'message' => 'The '.realpath($path).' directory is not writable.',
                    'detail' => 'Please chmod this directory'.($path !== public_path() ? ' and its contents' : '').' to 0775.'
                ];
            }
        }
    }
}
