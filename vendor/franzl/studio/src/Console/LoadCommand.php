<?php

namespace Studio\Console;

use Studio\Config\Config;
use Symfony\Component\Console\Input\InputArgument;

class LoadCommand extends BaseCommand
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
            ->setName('load')
            ->setDescription('Load a path to be managed with Studio')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path where the package files are located'
            );
    }

    protected function fire()
    {
        $this->config->addPath(
            $path = $this->input->getArgument('path')
        );

        $this->io->success("Packages matching the path $path will now be loaded by Composer.");
    }
}
