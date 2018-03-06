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

class RevisionCompiler implements CompilerInterface
{
    /**
     * @var string[]
     */
    protected $files = [];

    /**
     * @var callable[]
     */
    protected $strings = [];

    /**
     * @var bool
     */
    protected $watch;

    /**
     * @param string $path
     * @param string $filename
     * @param bool $watch
     */
    public function __construct($path, $filename, $watch = false)
    {
        $this->path = $path;
        $this->filename = $filename;
        $this->watch = $watch;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * {@inheritdoc}
     */
    public function addFile($file)
    {
        $this->files[] = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function addString(callable $callback)
    {
        $this->strings[] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function getFile()
    {
        $old = $current = $this->getRevision();

        $ext = pathinfo($this->filename, PATHINFO_EXTENSION);
        $file = $this->path.'/'.substr_replace($this->filename, '-'.$old, -strlen($ext) - 1, 0);

        if ($this->watch || ! $old) {
            $cacheDifferentiator = [$this->getCacheDifferentiator()];

            foreach ($this->files as $source) {
                $cacheDifferentiator[] = [$source, filemtime($source)];
            }

            $current = hash('crc32b', serialize($cacheDifferentiator));
        }

        $exists = file_exists($file);

        if (! $exists || $old !== $current) {
            if ($exists) {
                unlink($file);
            }

            $file = $this->path.'/'.substr_replace($this->filename, '-'.$current, -strlen($ext) - 1, 0);

            if ($content = $this->compile()) {
                $this->putRevision($current);

                file_put_contents($file, $content);
            } else {
                return;
            }
        }

        return $file;
    }

    /**
     * @return mixed
     */
    protected function getCacheDifferentiator()
    {
    }

    /**
     * @param string $string
     * @return string
     */
    protected function format($string)
    {
        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function compile()
    {
        $output = '';

        foreach ($this->files as $file) {
            $output .= $this->formatFile($file);
        }

        foreach ($this->strings as $callback) {
            $output .= $this->format($callback());
        }

        return $output;
    }

    /**
     * @param string $file
     * @return string
     */
    protected function formatFile($file)
    {
        return $this->format(file_get_contents($file));
    }

    /**
     * @return string
     */
    protected function getRevisionFile()
    {
        return $this->path.'/rev-manifest.json';
    }

    /**
     * @return string|null
     */
    protected function getRevision()
    {
        if (file_exists($file = $this->getRevisionFile())) {
            $manifest = json_decode(file_get_contents($file), true);

            return array_get($manifest, $this->filename);
        }
    }

    /**
     * @param string $revision
     * @return int
     */
    protected function putRevision($revision)
    {
        if (file_exists($file = $this->getRevisionFile())) {
            $manifest = json_decode(file_get_contents($file), true);
        } else {
            $manifest = [];
        }

        $manifest[$this->filename] = $revision;

        return file_put_contents($this->getRevisionFile(), json_encode($manifest));
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $revision = $this->getRevision();

        $ext = pathinfo($this->filename, PATHINFO_EXTENSION);

        $file = $this->path.'/'.substr_replace($this->filename, '-'.$revision, -strlen($ext) - 1, 0);

        if (file_exists($file)) {
            unlink($file);
        }
    }
}
