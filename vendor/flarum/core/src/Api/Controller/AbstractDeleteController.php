<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

abstract class AbstractDeleteController implements ControllerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $this->delete($request);

        return new EmptyResponse(204);
    }

    /**
     * Delete the resource.
     *
     * @param ServerRequestInterface $request
     */
    abstract protected function delete(ServerRequestInterface $request);
}
