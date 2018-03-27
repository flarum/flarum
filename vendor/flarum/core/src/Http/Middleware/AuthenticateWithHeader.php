<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Api\ApiKey;
use Flarum\Core\User;
use Flarum\Http\AccessToken;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class AuthenticateWithHeader implements MiddlewareInterface
{
    /**
     * @var string
     */
    protected $prefix = 'Token ';

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $headerLine = $request->getHeaderLine('authorization');

        $parts = explode(';', $headerLine);

        if (isset($parts[0]) && starts_with($parts[0], $this->prefix)) {
            $id = substr($parts[0], strlen($this->prefix));

            if (isset($parts[1])) {
                if (ApiKey::find($id)) {
                    $actor = $this->getUser($parts[1]);

                    $request = $request->withAttribute('bypassFloodgate', true);
                }
            } elseif ($token = AccessToken::find($id)) {
                $token->touch();

                $actor = $token->user;
            }

            if (isset($actor)) {
                $request = $request->withAttribute('actor', $actor);
                $request = $request->withoutAttribute('session');
            }
        }

        return $out ? $out($request, $response) : $response;
    }

    private function getUser($string)
    {
        $parts = explode('=', trim($string));

        if (isset($parts[0]) && $parts[0] === 'userId') {
            return User::find($parts[1]);
        }
    }
}
