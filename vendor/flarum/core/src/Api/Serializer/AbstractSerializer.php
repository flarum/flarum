<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Closure;
use DateTime;
use Flarum\Core\User;
use Flarum\Event\GetApiRelationship;
use Flarum\Event\PrepareApiAttributes;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use LogicException;
use Tobscure\JsonApi\AbstractSerializer as BaseAbstractSerializer;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Relationship;
use Tobscure\JsonApi\Resource;
use Tobscure\JsonApi\SerializerInterface;

abstract class AbstractSerializer extends BaseAbstractSerializer
{
    /**
     * @var User
     */
    protected $actor;

    /**
     * @var Dispatcher
     */
    protected static $dispatcher;

    /**
     * @var Container
     */
    protected static $container;

    /**
     * @return User
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * @param User $actor
     */
    public function setActor(User $actor)
    {
        $this->actor = $actor;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($model, array $fields = null)
    {
        if (! is_object($model) && ! is_array($model)) {
            return [];
        }

        $attributes = $this->getDefaultAttributes($model);

        static::$dispatcher->fire(
            new PrepareApiAttributes($this, $model, $attributes)
        );

        return $attributes;
    }

    /**
     * Get the default set of serialized attributes for a model.
     *
     * @param object|array $model
     * @return array
     */
    abstract protected function getDefaultAttributes($model);

    /**
     * @param DateTime|null $date
     * @return string|null
     */
    protected function formatDate(DateTime $date = null)
    {
        if ($date) {
            return $date->format(DateTime::RFC3339);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationship($model, $name)
    {
        if ($relationship = $this->getCustomRelationship($model, $name)) {
            return $relationship;
        }

        return parent::getRelationship($model, $name);
    }

    /**
     * Get a custom relationship.
     *
     * @param mixed $model
     * @param string $name
     * @return Relationship|null
     */
    protected function getCustomRelationship($model, $name)
    {
        $relationship = static::$dispatcher->until(
            new GetApiRelationship($this, $name, $model)
        );

        if ($relationship && ! ($relationship instanceof Relationship)) {
            throw new LogicException(
                'GetApiRelationship handler must return an instance of '.Relationship::class
            );
        }

        return $relationship;
    }

    /**
     * Get a relationship builder for a has-one relationship.
     *
     * @param mixed $model
     * @param string|Closure|\Tobscure\JsonApi\SerializerInterface $serializer
     * @param string|Closure|null $relation
     * @return Relationship
     */
    public function hasOne($model, $serializer, $relation = null)
    {
        return $this->buildRelationship($model, $serializer, $relation);
    }

    /**
     * Get a relationship builder for a has-many relationship.
     *
     * @param mixed $model
     * @param string|Closure|\Tobscure\JsonApi\SerializerInterface $serializer
     * @param string|null $relation
     * @return Relationship
     */
    public function hasMany($model, $serializer, $relation = null)
    {
        return $this->buildRelationship($model, $serializer, $relation, true);
    }

    /**
     * @param mixed $model
     * @param string|Closure|\Tobscure\JsonApi\SerializerInterface $serializer
     * @param string|null $relation
     * @param bool $many
     * @return Relationship
     */
    protected function buildRelationship($model, $serializer, $relation = null, $many = false)
    {
        if (is_null($relation)) {
            list(, , $caller) = debug_backtrace(false, 3);

            $relation = $caller['function'];
        }

        $data = $this->getRelationshipData($model, $relation);

        if ($data) {
            $serializer = $this->resolveSerializer($serializer, $model, $data);

            $type = $many ? Collection::class : Resource::class;

            $element = new $type($data, $serializer);

            return new Relationship($element);
        }
    }

    /**
     * @param mixed $model
     * @param string $relation
     * @return mixed
     */
    protected function getRelationshipData($model, $relation)
    {
        if (is_object($model)) {
            return $model->$relation;
        } elseif (is_array($model)) {
            return $model[$relation];
        }
    }

    /**
     * @param mixed $serializer
     * @param mixed $model
     * @param mixed $data
     * @return SerializerInterface
     * @throws InvalidArgumentException
     */
    protected function resolveSerializer($serializer, $model, $data)
    {
        if ($serializer instanceof Closure) {
            $serializer = call_user_func($serializer, $model, $data);
        }

        if (is_string($serializer)) {
            $serializer = $this->resolveSerializerClass($serializer);
        }

        if (! ($serializer instanceof SerializerInterface)) {
            throw new InvalidArgumentException('Serializer must be an instance of '
                .SerializerInterface::class);
        }

        return $serializer;
    }

    /**
     * @param string $class
     * @return object
     */
    protected function resolveSerializerClass($class)
    {
        $serializer = static::$container->make($class);

        $serializer->setActor($this->actor);

        return $serializer;
    }

    /**
     * @return Dispatcher
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    public static function setEventDispatcher(Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * @return Container
     */
    public static function getContainer()
    {
        return static::$container;
    }

    /**
     * @param Container $container
     */
    public static function setContainer(Container $container)
    {
        static::$container = $container;
    }
}
