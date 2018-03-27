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

use JsonSerializable;

class Document implements JsonSerializable
{
    use LinksTrait;
    use MetaTrait;

    /**
     * The included array.
     *
     * @var array
     */
    protected $included = [];

    /**
     * The errors array.
     *
     * @var array
     */
    protected $errors;

    /**
     * The jsonapi array.
     *
     * @var array
     */
    protected $jsonapi;

    /**
     * The data object.
     *
     * @var ElementInterface
     */
    protected $data;

    /**
     * @param ElementInterface $data
     */
    public function __construct(ElementInterface $data = null)
    {
        $this->data = $data;
    }

    /**
     * Get included resources.
     *
     * @param ElementInterface $element
     * @param bool $includeParent
     * @return Resource[]
     */
    protected function getIncluded(ElementInterface $element, $includeParent = false)
    {
        $included = [];

        foreach ($element->getResources() as $resource) {
            if ($resource->isIdentifier()) {
                continue;
            }

            if ($includeParent) {
                $included = $this->mergeResource($included, $resource);
            } else {
                $type = $resource->getType();
                $id = $resource->getId();
            }

            foreach ($resource->getUnfilteredRelationships() as $relationship) {
                $includedElement = $relationship->getData();

                if (! $includedElement instanceof ElementInterface) {
                    continue;
                }

                foreach ($this->getIncluded($includedElement, true) as $child) {
                    // If this resource is the same as the top-level "data"
                    // resource, then we don't want it to show up again in the
                    // "included" array.
                    if (! $includeParent && $child->getType() === $type && $child->getId() === $id) {
                        continue;
                    }

                    $included = $this->mergeResource($included, $child);
                }
            }
        }

        $flattened = [];

        array_walk_recursive($included, function ($a) use (&$flattened) {
            $flattened[] = $a;
        });

        return $flattened;
    }

    /**
     * @param Resource[] $resources
     * @param Resource $newResource
     * @return Resource[]
     */
    protected function mergeResource(array $resources, Resource $newResource)
    {
        $type = $newResource->getType();
        $id = $newResource->getId();

        if (isset($resources[$type][$id])) {
            $resources[$type][$id]->merge($newResource);
        } else {
            $resources[$type][$id] = $newResource;
        }

        return $resources;
    }

    /**
     * Set the data object.
     *
     * @param ElementInterface $element
     * @return $this
     */
    public function setData(ElementInterface $element)
    {
        $this->data = $element;

        return $this;
    }

    /**
     * Set the errors array.
     *
     * @param array $errors
     * @return $this
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Set the jsonapi array.
     *
     * @param array $jsonapi
     * @return $this
     */
    public function setJsonapi($jsonapi)
    {
        $this->jsonapi = $jsonapi;

        return $this;
    }

    /**
     * Map everything to arrays.
     *
     * @return array
     */
    public function toArray()
    {
        $document = [];

        if (! empty($this->links)) {
            $document['links'] = $this->links;
        }

        if (! empty($this->data)) {
            $document['data'] = $this->data->toArray();

            $resources = $this->getIncluded($this->data);

            if (count($resources)) {
                $document['included'] = array_map(function (Resource $resource) {
                    return $resource->toArray();
                }, $resources);
            }
        }

        if (! empty($this->meta)) {
            $document['meta'] = $this->meta;
        }

        if (! empty($this->errors)) {
            $document['errors'] = $this->errors;
        }

        if (! empty($this->jsonapi)) {
            $document['jsonapi'] = $this->jsonapi;
        }

        return $document;
    }

    /**
     * Map to string.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }

    /**
     * Serialize for JSON usage.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
