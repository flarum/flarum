<?php

namespace Studio\Parts;

use Closure;
use Studio\Filesystem\Directory;

abstract class AbstractPart implements PartInterface
{
    /**
     * @var PartInputInterface
     */
    protected $input;


    abstract public function setupPackage($composer, Directory $target);

    public function setInput(PartInputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    protected function copyTo($file, Directory $target, $targetName = null, Closure $handler = null)
    {
        $targetName = $targetName ?: basename($file);

        $content = file_get_contents($file);

        if ($handler) {
            $content = $handler($content);
        }

        $target->write($targetName, $content);
    }
}
