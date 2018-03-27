<?php

namespace Studio\Console;

use Studio\Package;
use Studio\Config\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;

class ScrapCommand extends BaseCommand
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
            ->setName('scrap')
            ->setDescription('Delete a previously created package skeleton')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path where the package resides'
            );
    }

    protected function fire()
    {
        $path = $this->input->getArgument('path');

        if ($this->abortDeletion($path)) {
            $this->io->note('Aborted.');
            return;
        }

        $package = Package::fromFolder($path);
        $this->config->removePackage($package);

        $this->io->note('Removing package...');
        $filesystem = new Filesystem;
        $filesystem->remove($path);
        $this->io->success('Package successfully removed.');
    }

    protected function abortDeletion($path)
    {
        $this->io->caution("This will delete the entire $path folder and all files within.");

        return ! $this->io->confirm(
            "<question>Do you really want to scrap the package at $path?</question> ",
            false
        );
    }
}
