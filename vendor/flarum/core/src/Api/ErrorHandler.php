<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Exception;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\ErrorHandler as JsonApiErrorHandler;

class ErrorHandler
{
    /**
     * @var JsonApiErrorHandler
     */
    protected $errorHandler;

    /**
     * @param JsonApiErrorHandler $errorHandler
     */
    public function __construct(JsonApiErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param Exception $e
     * @return JsonApiResponse
     */
    public function handle(Exception $e)
    {
        $response = $this->errorHandler->handle($e);

        $document = new Document;
        $document->setErrors($response->getErrors());

        return new JsonApiResponse($document, $response->getStatus());
    }
}
