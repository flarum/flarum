<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

class FileDataProvider implements DataProviderInterface
{
    protected $default;
    protected $baseUrl = null;
    protected $databaseConfiguration = [];
    protected $adminUser = [];
    protected $settings = [];

    public function __construct(InputInterface $input)
    {
        // Get default configuration
        $this->default = new DefaultsDataProvider();

        // Get configuration file path
        $configurationFile = $input->getOption('file');

        // Check if file exists before parsing content
        if (file_exists($configurationFile)) {
            $configurationFileContents = file_get_contents($configurationFile);
            // Try parsing JSON
            if (($json = json_decode($configurationFileContents, true)) !== null) {
                //Use JSON if Valid
                $configuration = $json;
            } else {
                //Else use YAML
                $configuration = Yaml::parse($configurationFileContents);
            }

            // Define configuration variables
            $this->baseUrl = isset($configuration['baseUrl']) ? rtrim($configuration['baseUrl'], '/') : null;
            $this->databaseConfiguration = isset($configuration['databaseConfiguration']) ? $configuration['databaseConfiguration'] : [];
            $this->adminUser = isset($configuration['adminUser']) ? $configuration['adminUser'] : [];
            $this->settings = isset($configuration['settings']) ? $configuration['settings'] : [];
        } else {
            throw new Exception('Configuration file does not exist.');
        }
    }

    public function getDatabaseConfiguration()
    {
        return $this->databaseConfiguration + $this->default->getDatabaseConfiguration();
    }

    public function getBaseUrl()
    {
        return (! is_null($this->baseUrl)) ? $this->baseUrl : $this->default->getBaseUrl();
    }

    public function getAdminUser()
    {
        return $this->adminUser + $this->default->getAdminUser();
    }

    public function getSettings()
    {
        return $this->settings + $this->default->getSettings();
    }
}
