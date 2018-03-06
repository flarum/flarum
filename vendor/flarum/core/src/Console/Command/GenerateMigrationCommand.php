<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Console\Command;

use Flarum\Database\MigrationCreator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateMigrationCommand extends AbstractCommand
{
    /**
     * @var MigrationCreator
     */
    protected $creator;

    /**
     * @param MigrationCreator $creator
     */
    public function __construct(MigrationCreator $creator)
    {
        parent::__construct();

        $this->creator = $creator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:migration')
            ->setDescription('Generate a migration')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name of the migration.'
            )
            ->addOption(
                'extension',
                null,
                InputOption::VALUE_REQUIRED,
                'The extension to generate the migration for.'
            )
            ->addOption(
                'create',
                null,
                InputOption::VALUE_REQUIRED,
                'The table to be created.'
            )
            ->addOption(
                'table',
                null,
                InputOption::VALUE_REQUIRED,
                'The table to migrate.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $name = $this->input->getArgument('name');

        $extension = $this->input->getOption('extension');

        $table = $this->input->getOption('table');

        $create = $this->input->getOption('create');

        if (! $table && is_string($create)) {
            $table = $create;
        }

        $this->writeMigration($name, $extension, $table, $create);
    }

    /**
     * Write the migration file to disk.
     *
     * @param string $name
     * @param string $extension
     * @param string $table
     * @param bool $create
     * @return string
     */
    protected function writeMigration($name, $extension, $table, $create)
    {
        $path = $this->creator->create($name, $extension, $table, $create);

        $file = pathinfo($path, PATHINFO_FILENAME);

        $this->info("Created migration: $file");
    }
}
