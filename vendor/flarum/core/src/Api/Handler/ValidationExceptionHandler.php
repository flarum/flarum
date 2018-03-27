<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Handler;

use Exception;
use Flarum\Core\Exception\ValidationException;
use Tobscure\JsonApi\Exception\Handler\ExceptionHandlerInterface;
use Tobscure\JsonApi\Exception\Handler\ResponseBag;

class ValidationExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function manages(Exception $e)
    {
        return $e instanceof ValidationException;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Exception $e)
    {
        $errors = array_merge(
            $this->buildErrors($e->getAttributes(), '/data/attributes'),
            $this->buildErrors($e->getRelationships(), '/data/relationships')
        );

        return new ResponseBag(422, $errors);
    }

    private function buildErrors(array $messages, $pointer)
    {
        return array_map(function ($path, $detail) use ($pointer) {
            return [
                'status' => '422',
                'code' => 'validation_error',
                'detail' => $detail,
                'source' => ['pointer' => $pointer.'/'.$path]
            ];
        }, array_keys($messages), $messages);
    }
}
