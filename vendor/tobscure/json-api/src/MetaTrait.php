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

trait MetaTrait
{
    /**
     * The meta data array.
     *
     * @var array
     */
    protected $meta;

    /**
     * Get the meta.
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Set the meta data array.
     *
     * @param array $meta
     * @return $this
     */
    public function setMeta(array $meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Add meta data.
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addMeta($key, $value)
    {
        $this->meta[$key] = $value;

        return $this;
    }
}
