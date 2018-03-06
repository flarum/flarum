<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Core\AuthToken;
use Flarum\Core\User;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;

class AuthenticationResponseFactory
{
    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * AuthenticationResponseFactory constructor.
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     */
    public function __construct(SessionAuthenticator $authenticator, Rememberer $rememberer)
    {
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
    }

    public function make(Request $request, array $identification, array $suggestions = [])
    {
        if (isset($suggestions['username'])) {
            $suggestions['username'] = $this->sanitizeUsername($suggestions['username']);
        }

        $user = User::where($identification)->first();

        $payload = $this->getPayload($identification, $suggestions, $user);

        $response = $this->getResponse($payload);

        if ($user) {
            $session = $request->getAttribute('session');
            $this->authenticator->logIn($session, $user->id);

            $response = $this->rememberer->rememberUser($response, $user->id);
        }

        return $response;
    }

    /**
     * @param string $username
     * @return string
     */
    private function sanitizeUsername($username)
    {
        return preg_replace('/[^a-z0-9-_]/i', '', $username);
    }

    /**
     * @param array $payload
     * @return HtmlResponse
     */
    private function getResponse(array $payload)
    {
        $content = sprintf(
            '<script>window.opener.app.authenticationComplete(%s); window.close();</script>',
            json_encode($payload)
        );

        return new HtmlResponse($content);
    }

    /**
     * @param array $identification
     * @param array $suggestions
     * @param User|null $user
     * @return array
     */
    private function getPayload(array $identification, array $suggestions, User $user = null)
    {
        // If a user with these attributes already exists, then we will log them
        // in by generating an access token. Otherwise, we will generate a
        // unique token for these attributes and add it to the response, along
        // with the suggested account information.
        if ($user) {
            $payload = ['authenticated' => true];
        } else {
            $token = AuthToken::generate($identification);
            $token->save();

            $payload = array_merge($identification, $suggestions, ['token' => $token->id]);
        }

        return $payload;
    }
}
