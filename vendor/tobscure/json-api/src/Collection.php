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

class Collection implements ElementInterface
{
    /**
     * @var array
     */
    protected $resources = [];

    /**
     * Create a new collection instance.
     *
     * @param mixed $data
     * @param SerializerInterface $serializer
     */
    public function __construct($data, SerializerInterface $serializer)
    {
        $this->resources = $this->buildResources($data, $serializer);
    }

    /**
     * Convert an array of raw data to Resource objects.
     *
     * @param mixed $data
     * @param SerializerInterface $serializer
     * @return Resource[]
     */
    protected function buildResources($data, SerializerInterface $serializer)
    {
        $resources = [];

        foreach ($data as $resource) {
            if (! ($resource instanceof Resource)) {
                $resource = new Resource($resource, $serializer);
            }

            $resources[] = $resource;
        }

        return $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Set the resources array.
     *
     * @param array $resources
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    /**
     * Request a relationship to be included for all resources.
     *
     * @param string|array $relationships
     * @return $this
     */
    public function with($relationships)
    {
        foreach ($this->resources as $resource) {
            $resource->with($relationships);
        }

        return $this;
    }

    /**
     * Request a relationship to be identified for all resources.
     *
     * @param string|array $relationships
     * @return $this
     */
    public function identify($relationships)
    {
        foreach ($this->resources as $resource) {
            $resource->identify($relationships);
        }

        return $this;
    }

    /**
     * Request a restricted set of fields.
     *
     * @param array|null $fields
     * @return $this
     */
    public function fields($fields)
    {
        foreach ($this->resources as $resource) {
            $resource->fields($fields);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array_map(function (Resource $resource) {
            return $resource->toArray();
        }, $this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function toIdentifier()
    {
        return array_map(function (Resource $resource) {
            return $resource->toIdentifier();
        }, $this->resources);
    }
}
