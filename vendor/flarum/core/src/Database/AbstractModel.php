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

use Flarum\Event\ConfigureModelDates;
use Flarum\Event\ConfigureModelDefaultAttributes;
use Flarum\Event\GetModelRelationship;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

/**
 * Base model class, building on Eloquent.
 *
 * Adds the ability for custom relations to be added to a model during runtime.
 * These relations behave in the same way that you would expect; they can be
 * queried, eager loaded, and accessed as an attribute.
 */
abstract class AbstractModel extends Eloquent
{
    /**
     * Indicates if the model should be timestamped. Turn off by default.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * An array of callbacks to be run once after the model is saved.
     *
     * @var callable[]
     */
    protected $afterSaveCallbacks = [];

    /**
     * An array of callbacks to be run once after the model is deleted.
     *
     * @var callable[]
     */
    protected $afterDeleteCallbacks = [];

    /**
     * {@inheritdoc}
     */
    public static function boot()
    {
        parent::boot();

        static::saved(function (AbstractModel $model) {
            foreach ($model->releaseAfterSaveCallbacks() as $callback) {
                $callback($model);
            }
        });

        static::deleted(function (AbstractModel $model) {
            foreach ($model->releaseAfterDeleteCallbacks() as $callback) {
                $callback($model);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(array $attributes = [])
    {
        $defaults = [];

        static::$dispatcher->fire(
            new ConfigureModelDefaultAttributes($this, $defaults)
        );

        $this->attributes = $defaults;

        parent::__construct($attributes);
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        static $dates = [];

        $class = get_class($this);

        if (! isset($dates[$class])) {
            static::$dispatcher->fire(
                new ConfigureModelDates($this, $this->dates)
            );

            $dates[$class] = $this->dates;
        }

        return $dates[$class];
    }

    /**
     * Get an attribute from the model. If nothing is found, attempt to load
     * a custom relation method with this key.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (! is_null($value = parent::getAttribute($key))) {
            return $value;
        }

        // If a custom relation with this key has been set up, then we will load
        // and return results from the query and hydrate the relationship's
        // value on the "relationships" array.
        if (! $this->relationLoaded($key) && ($relation = $this->getCustomRelation($key))) {
            if (! $relation instanceof Relation) {
                throw new LogicException(
                    'Relationship method must return an object of type '.Relation::class
                );
            }

            return $this->relations[$key] = $relation->getResults();
        }
    }

    /**
     * Get a custom relation object.
     *
     * @param string $name
     * @return mixed
     */
    protected function getCustomRelation($name)
    {
        return static::$dispatcher->until(
            new GetModelRelationship($this, $name)
        );
    }

    /**
     * Register a callback to be run once after the model is saved.
     *
     * @param callable $callback
     * @return void
     */
    public function afterSave($callback)
    {
        $this->afterSaveCallbacks[] = $callback;
    }

    /**
     * Register a callback to be run once after the model is deleted.
     *
     * @param callable $callback
     * @return void
     */
    public function afterDelete($callback)
    {
        $this->afterDeleteCallbacks[] = $callback;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterSaveCallbacks()
    {
        $callbacks = $this->afterSaveCallbacks;

        $this->afterSaveCallbacks = [];

        return $callbacks;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterDeleteCallbacks()
    {
        $callbacks = $this->afterDeleteCallbacks;

        $this->afterDeleteCallbacks = [];

        return $callbacks;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $arguments)
    {
        if ($relation = $this->getCustomRelation($method)) {
            return $relation;
        }

        return parent::__call($method, $arguments);
    }
}
