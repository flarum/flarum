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

interface MailableInterface
{
    /**
     * Get the name of the view to construct a notification email with.
     *
     * @return string
     */
    public function getEmailView();

    /**
     * Get the subject line for a notification email.
     *
     * @return string
     */
    public function getEmailSubject();
}
