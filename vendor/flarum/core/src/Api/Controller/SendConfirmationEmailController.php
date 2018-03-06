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

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\EmailToken;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Forum\UrlGenerator;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Zend\Diactoros\Response\EmptyResponse;

class SendConfirmationEmailController implements ControllerInterface
{
    use AssertPermissionTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param \Flarum\Settings\SettingsRepositoryInterface $settings
     * @param Mailer $mailer
     * @param UrlGenerator $url
     * @param TranslatorInterface $translator
     */
    public function __construct(SettingsRepositoryInterface $settings, Mailer $mailer, UrlGenerator $url, TranslatorInterface $translator)
    {
        $this->settings = $settings;
        $this->mailer = $mailer;
        $this->url = $url;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $id = array_get($request->getQueryParams(), 'id');
        $actor = $request->getAttribute('actor');

        $this->assertRegistered($actor);

        if ($actor->id != $id || $actor->is_activated) {
            throw new PermissionDeniedException;
        }

        $token = EmailToken::generate($actor->email, $actor->id);
        $token->save();

        $data = [
            '{username}' => $actor->username,
            '{url}' => $this->url->toRoute('confirmEmail', ['token' => $token->id]),
            '{forum}' => $this->settings->get('forum_title')
        ];

        $body = $this->translator->trans('core.email.activate_account.body', $data);

        $this->mailer->raw($body, function (Message $message) use ($actor, $data) {
            $message->to($actor->email);
            $message->subject('['.$data['{forum}'].'] '.$this->translator->trans('core.email.activate_account.subject'));
        });

        return new EmptyResponse;
    }
}
