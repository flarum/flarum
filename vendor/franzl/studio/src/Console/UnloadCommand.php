<?php

namespace Studio\Console;

use Studio\Config\Config;
use Symfony\Component\Console\Input\InputArgument;

class UnloadCommand extends BaseCommand
{
    protected $config;

    public function __construct(Config $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    protected function configure()
    {
        $this
            ->setName('unload')
            ->setDescription('Unload a package path from being managed with Studio')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path where the package files are located'
            );
    }

    protected function fire()
    {
        $this->config->removePath(
            $path = $this->input->getArgument('path')
        );

        $this->io->success("Packages matching the path $path will no longer be loaded by Composer.");
    }
}
