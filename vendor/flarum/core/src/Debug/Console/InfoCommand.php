<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Debug\Console;

use Flarum\Console\Command\AbstractCommand;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;

class InfoCommand extends AbstractCommand
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param ExtensionManager $extensions
     * @param array $config
     */
    public function __construct(ExtensionManager $extensions, array $config)
    {
        $this->extensions = $extensions;
        $this->config = $config;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('info')
            ->setDescription("Gather information about Flarum's core and installed extensions");
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $coreVersion = $this->findPackageVersion(__DIR__.'/../../../', Application::VERSION);
        $this->info("Flarum core $coreVersion");

        $this->info('PHP '.PHP_VERSION);

        $phpExtensions = implode(', ', get_loaded_extensions());
        $this->info("Loaded extensions: $phpExtensions");

        foreach ($this->extensions->getEnabledExtensions() as $extension) {
            /* @var \Flarum\Extension\Extension $extension */
            $name = $extension->getId();
            $version = $this->findPackageVersion($extension->getPath(), $extension->getVersion());

            $this->info("EXT $name $version");
        }

        $this->info('Base URL: '.$this->config['url']);
        $this->info('Installation path: '.getcwd());
    }

    /**
     * Try to detect a package's exact version.
     *
     * If the package seems to be a Git version, we extract the currently
     * checked out commit using the command line.
     *
     * @param string $path
     * @param string $fallback
     * @return string
     */
    private function findPackageVersion($path, $fallback)
    {
        if (file_exists("$path/.git")) {
            $cwd = getcwd();
            chdir($path);

            $output = [];
            $status = null;
            exec('git rev-parse HEAD', $output, $status);

            chdir($cwd);

            if ($status == 0) {
                return "$fallback ($output[0])";
            }
        }

        return $fallback;
    }
}
