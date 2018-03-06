<?php

/*
 * This file is part of JSON-API.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tobscure\JsonApi\Exception\Handler;

/**
 * DTO to manage JSON error response handling.
 */
class ResponseBag
{
    private $status;
    private $errors;

    /**
     * @param int $status
     * @param array $errors
     */
    public function __construct($status, array $errors)
    {
        $this->status = $status;
        $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
