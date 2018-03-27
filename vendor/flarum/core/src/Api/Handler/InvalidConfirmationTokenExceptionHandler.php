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
use Flarum\Core\Exception\InvalidConfirmationTokenException;
use Tobscure\JsonApi\Exception\Handler\ExceptionHandlerInterface;
use Tobscure\JsonApi\Exception\Handler\ResponseBag;

class InvalidConfirmationTokenExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function manages(Exception $e)
    {
        return $e instanceof InvalidConfirmationTokenException;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Exception $e)
    {
        $status = 403;
        $error = [
            'status' => (string) $status,
            'code' => 'invalid_confirmation_token'
        ];

        return new ResponseBag($status, [$error]);
    }
}
