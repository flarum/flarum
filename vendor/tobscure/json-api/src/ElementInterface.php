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

interface ElementInterface
{
    /**
     * Get the resources array.
     *
     * @return array
     */
    public function getResources();

    /**
     * Map to a "resource object" array.
     *
     * @return array
     */
    public function toArray();

    /**
     * Map to a "resource object identifier" array.
     *
     * @return array
     */
    public function toIdentifier();

    /**
     * Request a relationship to be included.
     *
     * @param string|array $relationships
     * @return $this
     */
    public function with($relationships);

    /**
     * Request a restricted set of fields.
     *
     * @param array|null $fields
     * @return $this
     */
    public function fields($fields);
}
