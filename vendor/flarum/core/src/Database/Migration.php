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

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * Migration factory.
 *
 * Implements some handy shortcuts for creating typical migrations.
 */
abstract class Migration
{
    /**
     * Create a table.
     */
    public static function createTable($name, callable $definition)
    {
        return [
            'up' => function (Builder $schema) use ($name, $definition) {
                $schema->create($name, $definition);
            },
            'down' => function (Builder $schema) use ($name) {
                $schema->drop($name);
            }
        ];
    }

    /**
     * Rename a table.
     */
    public static function renameTable($from, $to)
    {
        return [
            'up' => function (Builder $schema) use ($from, $to) {
                $schema->rename($from, $to);
            },
            'down' => function (Builder $schema) use ($from, $to) {
                $schema->rename($to, $from);
            }
        ];
    }

    /**
     * Add columns to a table.
     */
    public static function addColumns($tableName, array $columnDefinitions)
    {
        return [
            'up' => function (Builder $schema) use ($tableName, $columnDefinitions) {
                $schema->table($tableName, function (Blueprint $table) use ($columnDefinitions) {
                    foreach ($columnDefinitions as $columnName => $options) {
                        $type = array_shift($options);
                        $table->addColumn($type, $columnName, $options);
                    }
                });
            },
            'down' => function (Builder $schema) use ($tableName, $columnDefinitions) {
                $schema->table($tableName, function (Blueprint $table) use ($columnDefinitions) {
                    $table->dropColumn(array_keys($columnDefinitions));
                });
            }
        ];
    }

    /**
     * Rename a column.
     */
    public static function renameColumn($tableName, $from, $to)
    {
        return [
            'up' => function (Builder $schema) use ($tableName, $from, $to) {
                $schema->table($tableName, function (Blueprint $table) use ($from, $to) {
                    $table->renameColumn($from, $to);
                });
            },
            'down' => function (Builder $schema) use ($tableName, $from, $to) {
                $schema->table($tableName, function (Blueprint $table) use ($from, $to) {
                    $table->renameColumn($to, $from);
                });
            }
        ];
    }

    /**
     * Add default values for config values.
     */
    public static function addSettings(array $defaults)
    {
        return [
            'up' => function (SettingsRepositoryInterface $settings) use ($defaults) {
                foreach ($defaults as $key => $value) {
                    $settings->set($key, $value);
                }
            },
            'down' => function (SettingsRepositoryInterface $settings) use ($defaults) {
                foreach (array_keys($defaults) as $key) {
                    $settings->delete($key);
                }
            }
        ];
    }

    /**
     * Add default permissions.
     */
    public static function addPermissions(array $permissions)
    {
        $keys = [];

        foreach ($permissions as $permission => $groups) {
            foreach ((array) $groups as $group) {
                $keys[] = [
                    'group_id' => $group,
                    'permission' => $permission,
                ];
            }
        }

        return [
            'up' => function (ConnectionInterface $db) use ($keys) {
                foreach ($keys as $key) {
                    $instance = $db->table('permissions')->where($key)->first();

                    if (is_null($instance)) {
                        $db->table('permissions')->insert($key);
                    }
                }
            },

            'down' => function (ConnectionInterface $db) use ($keys) {
                foreach ($keys as $key) {
                    $db->table('permissions')->where($key)->delete();
                }
            }
        ];
    }
}
