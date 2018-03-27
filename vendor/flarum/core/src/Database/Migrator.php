<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Database;

use Exception;
use Flarum\Extension\Extension;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Filesystem\Filesystem;

class Migrator
{
    /**
     * The migration repository implementation.
     *
     * @var \Flarum\Database\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * Create a new migrator instance.
     *
     * @param  MigrationRepositoryInterface  $repository
     * @param  Resolver                      $resolver
     * @param  Filesystem                    $files
     */
    public function __construct(
        MigrationRepositoryInterface $repository,
        Resolver $resolver,
        Filesystem $files
    ) {
        $this->files = $files;
        $this->resolver = $resolver;
        $this->repository = $repository;
    }

    /**
     * Run the outstanding migrations at a given path.
     *
     * @param  string    $path
     * @param  Extension $extension
     * @return void
     */
    public function run($path, Extension $extension = null)
    {
        $this->notes = [];

        $files = $this->getMigrationFiles($path);

        $ran = $this->repository->getRan($extension ? $extension->getId() : null);

        $migrations = array_diff($files, $ran);

        $this->runMigrationList($path, $migrations, $extension);
    }

    /**
     * Run an array of migrations.
     *
     * @param  string    $path
     * @param  array     $migrations
     * @param  Extension $extension
     * @return void
     */
    public function runMigrationList($path, $migrations, Extension $extension = null)
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the migrations have been run against this database system.
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        // Once we have the array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        foreach ($migrations as $file) {
            $this->runUp($path, $file, $extension);
        }
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string    $path
     * @param  string    $file
     * @param  string    $path
     * @param  Extension $extension
     * @return void
     */
    protected function runUp($path, $file, Extension $extension = null)
    {
        $migration = $this->resolve($path, $file);

        $this->runClosureMigration($migration);

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($file, $extension ? $extension->getId() : null);

        $this->note("<info>Migrated:</info> $file");
    }

    /**
     * Rolls all of the currently applied migrations back.
     *
     * @param  string    $path
     * @param  Extension $extension
     * @return int
     */
    public function reset($path, Extension $extension = null)
    {
        $this->notes = [];

        $migrations = array_reverse($this->repository->getRan($extension->getId()));

        $count = count($migrations);

        if ($count === 0) {
            $this->note('<info>Nothing to rollback.</info>');
        } else {
            foreach ($migrations as $migration) {
                $this->runDown($path, $migration, $extension);
            }
        }

        return $count;
    }

    /**
     * Run "down" a migration instance.
     *
     * @param            $path
     * @param  string    $file
     * @param  string    $path
     * @param  Extension $extension
     * @return void
     */
    protected function runDown($path, $file, Extension $extension = null)
    {
        $migration = $this->resolve($path, $file);

        $this->runClosureMigration($migration, 'down');

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($file, $extension ? $extension->getId() : null);

        $this->note("<info>Rolled back:</info> $file");
    }

    /**
     * Runs a closure migration based on the migrate direction.
     *
     * @param        $migration
     * @param string $direction
     * @throws Exception
     */
    protected function runClosureMigration($migration, $direction = 'up')
    {
        if (is_array($migration) && array_key_exists($direction, $migration)) {
            app()->call($migration[$direction]);
        } else {
            throw new Exception('Migration file should contain an array with up/down.');
        }
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param  string $path
     * @return array
     */
    public function getMigrationFiles($path)
    {
        $files = $this->files->glob($path.'/*_*.php');

        if ($files === false) {
            return [];
        }

        $files = array_map(function ($file) {
            return str_replace('.php', '', basename($file));
        }, $files);

        // Once we have all of the formatted file names we will sort them and since
        // they all start with a timestamp this should give us the migrations in
        // the order they were actually created by the application developers.
        sort($files);

        return $files;
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string $path
     * @param  string $file
     * @return array
     */
    public function resolve($path, $file)
    {
        $migration = "$path/$file.php";

        if ($this->files->exists($migration)) {
            return $this->files->getRequire($migration);
        }
    }

    /**
     * Raise a note event for the migrator.
     *
     * @param  string $message
     * @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the notes for the last operation.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Resolve the database connection instance.
     *
     * @param  string $connection
     * @return \Illuminate\Database\Connection
     */
    public function resolveConnection($connection)
    {
        return $this->resolver->connection($connection);
    }

    /**
     * Set the default connection name.
     *
     * @param  string $name
     * @return void
     */
    public function setConnection($name)
    {
        if (! is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Get the migration repository instance.
     *
     * @return \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }

    /**
     * Get the file system instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
