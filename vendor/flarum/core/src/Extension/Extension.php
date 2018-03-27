<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @property string $name
 * @property string $description
 * @property string $type
 * @property array  $keywords
 * @property string $homepage
 * @property string $time
 * @property string $license
 * @property array  $authors
 * @property array  $support
 * @property array  $require
 * @property array  $requireDev
 * @property array  $autoload
 * @property array  $autoloadDev
 * @property array  $conflict
 * @property array  $replace
 * @property array  $provide
 * @property array  $suggest
 * @property array  $extra
 */
class Extension implements Arrayable
{
    /**
     * Unique Id of the extension.
     *
     * @info    Identical to the directory in the extensions directory.
     * @example flarum_suspend
     *
     * @var string
     */
    protected $id;
    /**
     * The directory of this extension.
     *
     * @var string
     */
    protected $path;

    /**
     * Composer json of the package.
     *
     * @var array
     */
    protected $composerJson;

    /**
     * Whether the extension is installed.
     *
     * @var bool
     */
    protected $installed = true;

    /**
     * The installed version of the extension.
     *
     * @var string
     */
    protected $version;

    /**
     * Whether the extension is enabled.
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * @param       $path
     * @param array $composerJson
     */
    public function __construct($path, $composerJson)
    {
        $this->path = $path;
        $this->composerJson = $composerJson;
        $this->assignId();
    }

    /**
     * Assigns the id for the extension used globally.
     */
    protected function assignId()
    {
        list($vendor, $package) = explode('/', $this->name);
        $package = str_replace(['flarum-ext-', 'flarum-'], '', $package);
        $this->id = "$vendor-$package";
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->composerJsonAttribute(Str::snake($name, '-'));
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name)
    {
        return isset($this->{$name}) || $this->composerJsonAttribute(Str::snake($name, '-'));
    }

    /**
     * Dot notation getter for composer.json attributes.
     *
     * @see https://laravel.com/docs/5.1/helpers#arrays
     *
     * @param $name
     * @return mixed
     */
    public function composerJsonAttribute($name)
    {
        return Arr::get($this->composerJson, $name);
    }

    /**
     * @param bool $installed
     * @return Extension
     */
    public function setInstalled($installed)
    {
        $this->installed = $installed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        return $this->installed;
    }

    /**
     * @param string $version
     * @return Extension
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Loads the icon information from the composer.json.
     *
     * @return array|null
     */
    public function getIcon()
    {
        if (($icon = $this->composerJsonAttribute('extra.flarum-extension.icon'))) {
            if ($file = Arr::get($icon, 'image')) {
                $file = $this->path.'/'.$file;

                if (file_exists($file)) {
                    $mimetype = pathinfo($file, PATHINFO_EXTENSION) === 'svg'
                        ? 'image/svg+xml'
                        : finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
                    $data = file_get_contents($file);

                    $icon['backgroundImage'] = 'url(\'data:'.$mimetype.';base64,'.base64_encode($data).'\')';
                }
            }

            return $icon;
        }
    }

    /**
     * @param bool $enabled
     * @return Extension
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * The raw path of the directory under extensions.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Tests whether the extension has assets.
     *
     * @return bool
     */
    public function hasAssets()
    {
        return realpath($this->path.'/assets/') !== false;
    }

    /**
     * Tests whether the extension has migrations.
     *
     * @return bool
     */
    public function hasMigrations()
    {
        return realpath($this->path.'/migrations/') !== false;
    }

    /**
     * Generates an array result for the object.
     *
     * @return array
     */
    public function toArray()
    {
        return (array) array_merge([
            'id'            => $this->getId(),
            'version'       => $this->getVersion(),
            'path'          => $this->path,
            'icon'          => $this->getIcon(),
            'hasAssets'     => $this->hasAssets(),
            'hasMigrations' => $this->hasMigrations(),
        ], $this->composerJson);
    }
}
