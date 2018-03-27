<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Asset;

interface CompilerInterface
{
    /**
     * @param string $filename
     */
    public function setFilename($filename);

    /**
     * @param string $file
     */
    public function addFile($file);

    /**
     * @param callable $callback
     */
    public function addString(callable $callback);

    /**
     * @return string
     */
    public function getFile();

    /**
     * @return string
     */
    public function compile();

    public function flush();
}
