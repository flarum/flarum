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

class PhpExtensions extends AbstractPrerequisite
{
    protected $extensions;

    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function check()
    {
        foreach ($this->extensions as $extension) {
            if (! extension_loaded($extension)) {
                $this->errors[] = [
                    'message' => "The PHP extension '$extension' is required.",
                ];
            }
        }
    }
}
