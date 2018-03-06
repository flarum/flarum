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

class Resource implements ElementInterface
{
    use LinksTrait;
    use MetaTrait;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * A list of relationships to include.
     *
     * @var array
     */
    protected $includes = [];

    /**
     * A list of fields to restrict to.
     *
     * @var array|null
     */
    protected $fields;

    /**
     * An array of Resources that should be merged into this one.
     *
     * @var Resource[]
     */
    protected $merged = [];

    /**
     * @param mixed $data
     * @param SerializerInterface $serializer
     */
    public function __construct($data, SerializerInterface $serializer)
    {
        $this->data = $data;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        return [$this];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $array = $this->toIdentifier();

        $array['attributes'] = $this->getAttributes();

        $relationships = $this->getRelationshipsAsArray();

        if (count($relationships)) {
            $array['relationships'] = $relationships;
        }

        $links = [];
        if (! empty($this->links)) {
            $links = $this->links;
        }
        $serializerLinks = $this->serializer->getLinks($this->data);
        if (! empty($serializerLinks)) {
            $links = array_merge($serializerLinks, $links);
        }
        if (! empty($links)) {
            $array['links'] = $links;
        }

        $meta = [];
        if (! empty($this->meta)) {
            $meta = $this->meta;
        }
        $serializerMeta = $this->serializer->getMeta($this->data);
        if (! empty($serializerMeta)) {
            $meta = array_merge($serializerMeta, $meta);
        }
        if (! empty($meta)) {
            $array['meta'] = $meta;
        }

        return $array;
    }

    /**
     * Check whether or not this resource is an identifier (i.e. does it have
     * any data attached?).
     *
     * @return bool
     */
    public function isIdentifier()
    {
        return ! is_object($this->data) && ! is_array($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function toIdentifier()
    {
        $array = [
            'type' => $this->getType(),
            'id' => $this->getId()
        ];

        if (! empty($this->meta)) {
            $array['meta'] = $this->meta;
        }

        return $array;
    }

    /**
     * Get the resource type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->serializer->getType($this->data);
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getId()
    {
        if (! is_object($this->data) && ! is_array($this->data)) {
            return (string) $this->data;
        }

        return (string) $this->serializer->getId($this->data);
    }

    /**
     * Get the resource attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = (array) $this->serializer->getAttributes($this->data, $this->getOwnFields());

        $attributes = $this->filterFields($attributes);

        $attributes = $this->mergeAttributes($attributes);

        return $attributes;
    }

    /**
     * Get the requested fields for this resource type.
     *
     * @return array|null
     */
    protected function getOwnFields()
    {
        $type = $this->getType();

        if (isset($this->fields[$type])) {
            return $this->fields[$type];
        }
    }

    /**
     * Filter the given fields array (attributes or relationships) according
     * to the requested fieldset.
     *
     * @param array $fields
     * @return array
     */
    protected function filterFields(array $fields)
    {
        if ($requested = $this->getOwnFields()) {
            $fields = array_intersect_key($fields, array_flip($requested));
        }

        return $fields;
    }

    /**
     * Merge the attributes of merged resources into an array of attributes.
     *
     * @param array $attributes
     * @return array
     */
    protected function mergeAttributes(array $attributes)
    {
        foreach ($this->merged as $resource) {
            $attributes = array_replace_recursive($attributes, $resource->getAttributes());
        }

        return $attributes;
    }

    /**
     * Get the resource relationships.
     *
     * @return Relationship[]
     */
    public function getRelationships()
    {
        $relationships = $this->buildRelationships();

        return $this->filterFields($relationships);
    }

    /**
     * Get the resource relationships without considering requested ones.
     *
     * @return Relationship[]
     */
    public function getUnfilteredRelationships()
    {
        return $this->buildRelationships();
    }

    /**
     * Get the resource relationships as an array.
     *
     * @return array
     */
    public function getRelationshipsAsArray()
    {
        $relationships = $this->getRelationships();

        $relationships = $this->convertRelationshipsToArray($relationships);

        return $this->mergeRelationships($relationships);
    }

    /**
     * Get an array of built relationships.
     *
     * @return Relationship[]
     */
    protected function buildRelationships()
    {
        $paths = Util::parseRelationshipPaths($this->includes);

        $relationships = [];

        foreach ($paths as $name => $nested) {
            $relationship = $this->serializer->getRelationship($this->data, $name);

            if ($relationship) {
                $relationshipData = $relationship->getData();
                if ($relationshipData instanceof ElementInterface) {
                    $relationshipData->with($nested)->fields($this->fields);
                }

                $relationships[$name] = $relationship;
            }
        }

        return $relationships;
    }

    /**
     * Merge the relationships of merged resources into an array of
     * relationships.
     *
     * @param array $relationships
     * @return array
     */
    protected function mergeRelationships(array $relationships)
    {
        foreach ($this->merged as $resource) {
            $relationships = array_replace_recursive($relationships, $resource->getRelationshipsAsArray());
        }

        return $relationships;
    }

    /**
     * Convert the given array of Relationship objects into an array.
     *
     * @param Relationship[] $relationships
     * @return array
     */
    protected function convertRelationshipsToArray(array $relationships)
    {
        return array_map(function (Relationship $relationship) {
            return $relationship->toArray();
        }, $relationships);
    }

    /**
     * Merge a resource into this one.
     *
     * @param Resource $resource
     */
    public function merge(Resource $resource)
    {
        $this->merged[] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function with($relationships)
    {
        $this->includes = array_unique(array_merge($this->includes, (array) $relationships));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }
}
