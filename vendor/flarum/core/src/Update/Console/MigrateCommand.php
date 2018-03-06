<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Update\Console;

use Flarum\Console\Command\AbstractCommand;
use Illuminate\Contracts\Container\Container;

class MigrateCommand extends AbstractCommand
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Run outstanding migrations');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Migrating Flarum...');

        $this->upgrade();

        $this->info('DONE.');
    }

    public function upgrade()
    {
        $this->container->bind('Illuminate\Database\Schema\Builder', function ($container) {
            return $container->make('Illuminate\Database\ConnectionInterface')->getSchemaBuilder();
        });

        $migrator = $this->container->make('Flarum\Database\Migrator');

        $migrator->run(__DIR__.'/../../../migrations');

        foreach ($migrator->getNotes() as $note) {
            $this->info($note);
        }

        $extensions = $this->container->make('Flarum\Extension\ExtensionManager');

        foreach ($extensions->getExtensions() as $name => $extension) {
            if (! $extension->isEnabled()) {
                continue;
            }

            $this->info('Migrating extension: '.$name);

            $notes = $extensions->migrate($extension);

            foreach ($notes as $note) {
                $this->info($note);
            }
        }

        $this->container->make('Flarum\Settings\SettingsRepositoryInterface')->set('version', $this->container->version());
    }
}
