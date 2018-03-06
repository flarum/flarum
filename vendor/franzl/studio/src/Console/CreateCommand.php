<?php

namespace Studio\Console;

use Studio\Parts\ConsoleInput;
use Studio\Shell\Shell;
use Studio\Creator\CreatorInterface;
use Studio\Creator\GitRepoCreator;
use Studio\Creator\GitSubmoduleCreator;
use Studio\Creator\SkeletonCreator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class CreateCommand extends BaseCommand
{
    protected $partClasses = [
        'Studio\Parts\Base\Part',
        'Studio\Parts\Composer\Part',
        'Studio\Parts\PhpUnit\Part',
        'Studio\Parts\PhpSpec\Part',
        'Studio\Parts\TravisCI\Part',
    ];

    /**
     * @var \Studio\Parts\PartInputInterface
     */
    protected $partInput;


    protected function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Create a new package skeleton')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path where the new package should be created'
            )
            ->addOption(
                'git',
                'g',
                InputOption::VALUE_REQUIRED,
                'If set, this will download the given Git repository instead of creating a new one.'
            )
            ->addOption(
                'submodule',
                'gs',
                InputOption::VALUE_REQUIRED,
                'If set, this will download the given Git repository (as submodule) instead of creating a new one.'
            );
    }

    protected function fire()
    {
        $this->partInput = new ConsoleInput($this->io);

        $creator = $this->makeCreator($this->input);

        $package = $creator->create();

        $path = $package->getPath();
        $this->io->success("Package directory $path created.");

        $this->io->note('Running composer install for new package...');
        Shell::run('composer install --prefer-dist', $package->getPath());
        $this->io->success('Package successfully created.');

        if ($this->shouldLoadNewPackage()) {
            $this->getApplication()->find('load')->run(
                new ArrayInput(['path' => $path]),
                $this->output
            );
        }
    }

    /**
     * Build a package creator from the given input options.
     *
     * @param InputInterface $input
     * @return CreatorInterface
     */
    protected function makeCreator(InputInterface $input)
    {
        $path = $input->getArgument('path');

        if ($input->getOption('git')) {
            return new GitRepoCreator($input->getOption('git'), $path);
        } elseif ($input->getOption('submodule')) {
            return new GitSubmoduleCreator($input->getOption('submodule'), $path);
        } else {
            $creator = new SkeletonCreator($path);
            $this->installParts($creator);
            return $creator;
        }
    }

    protected function installParts(SkeletonCreator $creator)
    {
        $parts = $this->makeParts();

        foreach ($parts as $part) {
            $creator->addPart($part);
        }
    }

    /**
     * @return \Studio\Parts\AbstractPart[]
     */
    protected function makeParts()
    {
        return array_map(function ($class) {
            return (new $class)->setInput($this->partInput);
        }, $this->partClasses);
    }

    protected function shouldLoadNewPackage()
    {
        if (!file_exists('composer.json')) {
            return false;
        } else if (!file_exists('studio.json')) {
            return $this->io->confirm(
                'Do you want to load this package in the surrounding Composer package using Studio?',
                true
            );
        } else {
            return true;
        }
    }
}
