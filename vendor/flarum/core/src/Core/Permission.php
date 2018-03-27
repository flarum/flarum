<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * @todo document database columns with @property
 */
class Permission extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'permissions';

    /**
     * Define the relationship with the group that this permission is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo('Flarum\Core\Group', 'group_id');
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where('group_id', $this->group_id)
              ->where('permission', $this->permission);

        return $query;
    }

    /**
     * Get a map of permissions to the group IDs that have them.
     *
     * @return array[]
     */
    public static function map()
    {
        $permissions = [];

        foreach (static::get() as $permission) {
            $permissions[$permission->permission][] = (string) $permission->group_id;
        }

        return $permissions;
    }
}
