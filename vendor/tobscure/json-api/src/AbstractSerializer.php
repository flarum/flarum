<?php

/*
 * This file is part of JSON-API.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tobscure\JsonApi;

use LogicException;

abstract class AbstractSerializer implements SerializerInterface
{
    /**
     * The type.
     *
     * @var string
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    public function getType($model)
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getId($model)
    {
        return $model->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($model, array $fields = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLinks($model)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta($model)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function getRelationship($model, $name)
    {
        $method = $this->getRelationshipMethodName($name);

        if (method_exists($this, $method)) {
            $relationship = $this->$method($model);

            if ($relationship !== null && ! ($relationship instanceof Relationship)) {
                throw new LogicException('Relationship method must return null or an instance of Tobscure\JsonApi\Relationship');
            }

            return $relationship;
        }
    }

    /**
     * Get the serializer method name for the given relationship.
     *
     * kebab-case is converted into camelCase.
     *
     * @param string $name
     * @return string
     */
    private function getRelationshipMethodName($name)
    {
        if (stripos($name, '-')) {
            $name = lcfirst(implode('', array_map('ucfirst', explode('-', $name))));
        }

        return $name;
    }
}
