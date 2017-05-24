<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Installer;

use Composer\Console\Application;
use Phar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Filesystem;

class Installer
{

    const GITHUB_TOKEN = 'ec785da935d5535e151f7b3386190265f00e8fe2';

    /**
     * Absolute path to Flarum installation.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Absolute path to directory for storing temporary installation files.
     *
     * @var string
     */
    protected $tmpPath;

    /**
     * The installation path, temporary files needed for running the Installer.
     *
     * @var string
     */
    protected $installPath;

    public function __construct($basePath)
    {
        $this->setBasePath($basePath);
        $this->setTmpPath($this->getBasePath('/storage/tmp'));
        $this->setInstallPath();

        $this->setupEnvironment();
    }

    /**
     * Sets up environment specifics
     */
    public function setupEnvironment()
    {
        // prevent time out of page, drawback of running in browser
        @set_time_limit(0);

        error_reporting(E_ALL);

        // sets a home directory for storing information
        putenv('COMPOSER_HOME=' . $this->getTmpPath('/composer-home'));
        // prevents any interaction composer might require
        putenv('COMPOSER_NO_INTERACTION=true');

        // force memory to at least 1GB (default for composer) otherwise composer will run out of memory
        if (function_exists('ini_set')) {
            ini_set('memory_limit', '1G');
        }
    }

    /**
     * @param string $basePath
     * @return Installer
     */
    public function setBasePath($basePath)
    {
        $this->basePath = realpath($basePath);

        return $this;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getBasePath($path = '')
    {
        return $this->basePath . $path;
    }

    /**
     * @return Installer
     */
    public function setInstallPath()
    {
        $this->installPath = realpath(__DIR__ . '/../');

        return $this;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getInstallPath($path = '')
    {
        return $this->installPath . $path;
    }

    /**
     * @param string $tmpPath
     * @return Installer
     */
    public function setTmpPath($tmpPath)
    {
        $this->tmpPath = realpath($tmpPath);

        return $this;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getTmpPath($path = '')
    {
        return $this->tmpPath . $path;
    }

    /**
     * Reports about specific stages during installation.
     *
     * @param null $state
     * @return bool|string
     */
    protected function progressReport($state = null)
    {
        switch ($state) {
            case 'phar-downloaded':
                return file_exists($this->getTmpPath('/composer.phar'));
            case 'phar-extracted':
                return file_exists($this->getTmpPath('/composer-phar-extracted/vendor/autoload.php'));
            case 'dependencies-loaded':
                return file_exists($this->getBasePath('/vendor/autoload.php'));
            case 'cleaned-up':
                return $this->progressReport('dependencies-loaded') && !$this->progressReport('phar-extracted');
        }
    }

    /**
     * Generates a json payload identifying the current state of installing.
     *
     * @return json
     */
    public function getCurrentState()
    {
        $state = null;

        if ($this->progressReport('cleaned-up')) {
            $state = true;
        } elseif ($this->progressReport('dependencies-loaded')) {
            $state = 'Dependencies downloaded. Cleaning up ..';
        } elseif ($this->progressReport('phar-extracted')) {
            $state = 'Composer extracted. Loading dependencies, this will take the longest ..';
        } elseif ($this->progressReport('phar-downloaded')) {
            $state = 'Composer downloaded. Extracting ..';
        }

        return json_encode($state);
    }

    /**
     * Listens for HTTP connections.
     */
    public function listen()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->listenAjax();
        }
        // show temporary installation page
        ob_end_clean();
        ob_start();
        include $this->getInstallPath('/views/composer-installation.html');
        ob_end_flush();

        $this->runInstall();
    }

    /**
     * Listens for XHR calls.
     */
    public function listenAjax()
    {
        echo $this->getCurrentState();
    }

    /**
     * Runs the actual installation steps.
     */
    protected function runInstall()
    {
        if (!$this->progressReport('phar-extracted') && !$this->progressReport('phar-downloaded')) {
            $this->downloadComposerPhar();
        }
        if (!$this->progressReport('phar-extracted') && $this->progressReport('phar-downloaded')) {
            $this->extractComposerPhar();
        }
        if ($this->progressReport('phar-extracted')) {
            $this->installDependencies();
        }
        if ($this->progressReport('dependencies-loaded')) {
            $this->cleanUp();
        }
    }

    /**
     * Downloads the composer.phar.
     */
    protected function downloadComposerPhar()
    {
        $c = curl_init('https://getcomposer.org/composer.phar');
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true
        ]);
        $phar = curl_exec($c);
        curl_close($c);

        file_put_contents($this->getTmpPath('/composer.phar'), $phar);

        unset($phar);
    }

    /**
     * Extracts the composer.phar.
     */
    protected function extractComposerPhar()
    {
        $composer = new Phar($this->getTmpPath('/composer.phar'));
        $composer->extractTo($this->getTmpPath('/composer-phar-extracted'), null, true);

        unset($composer);
    }

    /**
     * Installs dependencies using composer phar packages.
     */
    protected function installDependencies()
    {
        require_once $this->getTmpPath('/composer-phar-extracted/vendor/autoload.php');

        chdir($this->getBasePath());

        // run the composer installation command
        $application = new Application();
        // disable auto exit
        $application->setAutoExit(false);

        // first set the github token to prevent installation errors
        $input = new ArrayInput([
            'command'                 => 'config',
            'github-oauth.github.com' => self::GITHUB_TOKEN
        ]);
        $application->run($input);

        // set the input for the composer install command
        $input = new ArrayInput([
            'command'               => 'install',
            '--no-dev'              => true,
            '--prefer-dist'         => true,
            '--optimize-autoloader' => true,
            '-q'                    => true
        ]);
        $application->run($input);

        unset($input, $application);

        chdir($this->getInstallPath());
    }

    /**
     * Cleans up all tmp files.
     */
    protected function cleanUp()
    {
        $fs = new Filesystem();

        $fs->remove([
            $this->getTmpPath('/composer.phar'),
            $this->getTmpPath('/composer-home'),
            $this->getTmpPath('/composer-phar-extracted'),
        ]);
    }
}