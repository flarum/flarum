<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Database\AbstractModel;

/**
 * @property string $id
 */
class ApiKey extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'api_keys';

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Generate an API key.
     *
     * @return static
     */
    public static function generate()
    {
        $key = new static;

        $key->id = str_random(40);

        return $key;
    }
}
