<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Auth\Twitter;

use Flarum\Forum\AuthenticationResponseFactory;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use League\OAuth1\Client\Server\Twitter;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\RedirectResponse;

class TwitterAuthController implements ControllerInterface
{
    /**
     * @var AuthenticationResponseFactory
     */
    protected $authResponse;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param AuthenticationResponseFactory $authResponse
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(AuthenticationResponseFactory $authResponse, SettingsRepositoryInterface $settings)
    {
        $this->authResponse = $authResponse;
        $this->settings = $settings;
    }

    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface|RedirectResponse
     */
    public function handle(Request $request)
    {
        $redirectUri = (string) $request->getAttribute('originalUri', $request->getUri())->withQuery('');

        $server = new Twitter([
            'identifier'   => $this->settings->get('flarum-auth-twitter.api_key'),
            'secret'       => $this->settings->get('flarum-auth-twitter.api_secret'),
            'callback_uri' => $redirectUri
        ]);

        $session = $request->getAttribute('session');

        $queryParams = $request->getQueryParams();
        $oAuthToken = array_get($queryParams, 'oauth_token');
        $oAuthVerifier = array_get($queryParams, 'oauth_verifier');

        if (! $oAuthToken || ! $oAuthVerifier) {
            $temporaryCredentials = $server->getTemporaryCredentials();

            $session->set('temporary_credentials', serialize($temporaryCredentials));
            $session->save();

            // Second part of OAuth 1.0 authentication is to redirect the
            // resource owner to the login screen on the server.
            $server->authorize($temporaryCredentials);
            exit;
        }

        // Retrieve the temporary credentials we saved before
        $temporaryCredentials = unserialize($session->get('temporary_credentials'));

        // We will now retrieve token credentials from the server
        $tokenCredentials = $server->getTokenCredentials($temporaryCredentials, $oAuthToken, $oAuthVerifier);

        $user = $server->getUserDetails($tokenCredentials);

        $identification = ['twitter_id' => $user->uid];
        $suggestions = [
            'username' => $user->nickname,
            'avatarUrl' => str_replace('_normal', '', $user->imageUrl)
        ];

        return $this->authResponse->make($request, $identification, $suggestions);
    }
}
