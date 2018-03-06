<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\AuthenticationResponseFactory;
use Flarum\Http\Controller\ControllerInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\RedirectResponse;

abstract class AbstractOAuth2Controller implements ControllerInterface
{
    /**
     * @var AuthenticationResponseFactory
     */
    protected $authResponse;

    /**
     * @var \League\OAuth2\Client\Provider\AbstractProvider
     */
    protected $provider;

    /**
     * The access token, once obtained.
     *
     * @var string
     */
    protected $token;

    /**
     * @param AuthenticationResponseFactory $authResponse
     */
    public function __construct(AuthenticationResponseFactory $authResponse)
    {
        $this->authResponse = $authResponse;
    }

    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface|RedirectResponse
     */
    public function handle(Request $request)
    {
        $redirectUri = (string) $request->getAttribute('originalUri', $request->getUri())->withQuery('');

        $this->provider = $this->getProvider($redirectUri);

        $session = $request->getAttribute('session');

        $queryParams = $request->getQueryParams();
        $code = array_get($queryParams, 'code');
        $state = array_get($queryParams, 'state');

        if (! $code) {
            $authUrl = $this->provider->getAuthorizationUrl($this->getAuthorizationUrlOptions());
            $session->set('oauth2state', $this->provider->getState());

            return new RedirectResponse($authUrl.'&display=popup');
        } elseif (! $state || $state !== $session->get('oauth2state')) {
            $session->forget('oauth2state');
            echo 'Invalid state. Please close the window and try again.';
            exit;
        }

        $this->token = $this->provider->getAccessToken('authorization_code', compact('code'));

        $owner = $this->provider->getResourceOwner($this->token);

        $identification = $this->getIdentification($owner);
        $suggestions = $this->getSuggestions($owner);

        return $this->authResponse->make($request, $identification, $suggestions);
    }

    /**
     * @param string $redirectUri
     * @return \League\OAuth2\Client\Provider\AbstractProvider
     */
    abstract protected function getProvider($redirectUri);

    /**
     * @return array
     */
    abstract protected function getAuthorizationUrlOptions();

    /**
     * @param ResourceOwnerInterface $resourceOwner
     * @return array
     */
    abstract protected function getIdentification(ResourceOwnerInterface $resourceOwner);

    /**
     * @param ResourceOwnerInterface $resourceOwner
     * @return array
     */
    abstract protected function getSuggestions(ResourceOwnerInterface $resourceOwner);
}
