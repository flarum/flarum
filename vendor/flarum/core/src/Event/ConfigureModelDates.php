<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Database\AbstractModel;

/**
 * The `ConfigureModelDates` event is called to retrieve a list of fields for a model
 * that should be converted into date objects.
 */
class ConfigureModelDates
{
    /**
     * @var AbstractModel
     */
    public $model;

    /**
     * @var array
     */
    public $dates;

    /**
     * @param AbstractModel $model
     * @param array $dates
     */
    public function __construct(AbstractModel $model, array &$dates)
    {
        $this->model = $model;
        $this->dates = &$dates;
    }

    /**
     * @param string $model
     * @return bool
     */
    public function isModel($model)
    {
        return $this->model instanceof $model;
    }
}
