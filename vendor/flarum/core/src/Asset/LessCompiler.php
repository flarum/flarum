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

use Less_Exception_Parser;
use Less_Parser;

class LessCompiler extends RevisionCompiler
{
    /**
     * @var string
     */
    protected $cachePath;

    /**
     * @param string $path
     * @param string $filename
     * @param bool $watch
     * @param string $cachePath
     */
    public function __construct($path, $filename, $watch, $cachePath)
    {
        parent::__construct($path, $filename, $watch);

        $this->cachePath = $cachePath;
    }

    /**
     * {@inheritdoc}
     */
    public function compile()
    {
        if (! count($this->files) || ! count($this->strings)) {
            return;
        }

        ini_set('xdebug.max_nesting_level', 200);

        $parser = new Less_Parser([
            'compress' => true,
            'cache_dir' => $this->cachePath,
            'import_dirs' => [
                base_path().'/vendor/components/font-awesome/less' => '',
            ],
        ]);

        try {
            foreach ($this->files as $file) {
                $parser->parseFile($file);
            }

            foreach ($this->strings as $callback) {
                $parser->parse($callback());
            }

            return $parser->getCss();
        } catch (Less_Exception_Parser $e) {
            // TODO: log an error somewhere?
        }
    }
}
