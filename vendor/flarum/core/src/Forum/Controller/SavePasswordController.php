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

use Flarum\Core\PasswordToken;
use Flarum\Core\Validator\UserValidator;
use Flarum\Forum\UrlGenerator;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\SessionAuthenticator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\RedirectResponse;

class SavePasswordController implements ControllerInterface
{
    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var UserValidator
     */
    protected $validator;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Factory
     */
    protected $validatorFactory;

    /**
     * @param UrlGenerator $url
     * @param SessionAuthenticator $authenticator
     * @param UserValidator $validator
     * @param Factory $validatorFactory
     */
    public function __construct(UrlGenerator $url, SessionAuthenticator $authenticator, UserValidator $validator, Factory $validatorFactory)
    {
        $this->url = $url;
        $this->authenticator = $authenticator;
        $this->validator = $validator;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function handle(Request $request)
    {
        $input = $request->getParsedBody();

        $token = PasswordToken::findOrFail(array_get($input, 'passwordToken'));

        $password = array_get($input, 'password');

        try {
            // todo: probably shouldn't use the user validator for this,
            // passwords should be validated separately
            $this->validator->assertValid(compact('password'));

            $validator = $this->validatorFactory->make($input, ['password' => 'required|confirmed']);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        } catch (ValidationException $e) {
            $request->getAttribute('session')->set('error', $e->errors()->first());

            return new RedirectResponse($this->url->toRoute('resetPassword', ['token' => $token->id]));
        }

        $token->user->changePassword($password);
        $token->user->save();

        $token->delete();

        $session = $request->getAttribute('session');
        $this->authenticator->logIn($session, $token->user->id);

        return new RedirectResponse($this->url->toBase());
    }
}
