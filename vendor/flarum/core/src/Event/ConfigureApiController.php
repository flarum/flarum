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

use Flarum\Api\Controller\AbstractSerializeController;

class ConfigureApiController
{
    /**
     * @var AbstractSerializeController
     */
    public $controller;

    /**
     * @param AbstractSerializeController $controller
     */
    public function __construct(AbstractSerializeController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param string $controller
     * @return bool
     */
    public function isController($controller)
    {
        return $this->controller instanceof $controller;
    }

    /**
     * Set the serializer that will serialize data for the endpoint.
     *
     * @param string $serializer
     */
    public function setSerializer($serializer)
    {
        $this->controller->serializer = $serializer;
    }

    /**
     * Include the given relationship by default.
     *
     * @param string|array $name
     */
    public function addInclude($name)
    {
        $this->controller->include = array_merge($this->controller->include, (array) $name);
    }

    /**
     * Don't include the given relationship by default.
     *
     * @param string $name
     */
    public function removeInclude($name)
    {
        array_forget($this->controller->include, $name);
    }

    /**
     * Make the given relationship available for inclusion.
     *
     * @param string $name
     */
    public function addOptionalInclude($name)
    {
        $this->controller->optionalInclude[] = $name;
    }

    /**
     * Don't allow the given relationship to be included.
     *
     * @param string $name
     */
    public function removeOptionalInclude($name)
    {
        array_forget($this->controller->optionalInclude, $name);
    }

    /**
     * Set the default number of results.
     *
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->controller->limit = $limit;
    }

    /**
     * Set the maximum number of results.
     *
     * @param int $max
     */
    public function setMaxLimit($max)
    {
        $this->controller->maxLimit = $max;
    }

    /**
     * Allow sorting results by the given field.
     *
     * @param string $field
     */
    public function addSortField($field)
    {
        $this->controller->sortFields[] = $field;
    }

    /**
     * Disallow sorting results by the given field.
     *
     * @param string $field
     */
    public function removeSortField($field)
    {
        array_forget($this->controller->sortFields, $field);
    }

    /**
     * Set the default sort order for the results.
     *
     * @param array $sort
     */
    public function setSort(array $sort)
    {
        $this->controller->sort = $sort;
    }
}
