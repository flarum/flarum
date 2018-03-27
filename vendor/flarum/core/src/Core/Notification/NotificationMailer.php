<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Notification;

use Flarum\Core\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

class NotificationMailer
{
    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param MailableInterface $blueprint
     * @param User $user
     */
    public function send(MailableInterface $blueprint, User $user)
    {
        $this->mailer->send(
            $blueprint->getEmailView(),
            compact('blueprint', 'user'),
            function (Message $message) use ($blueprint, $user) {
                $message->to($user->email, $user->username)
                        ->subject($blueprint->getEmailSubject());
            }
        );
    }
}
