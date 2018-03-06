<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionAuthenticator
{
    /**
     * @param SessionInterface $session
     * @param int $userId
     */
    public function logIn(SessionInterface $session, $userId)
    {
        $session->migrate();
        $session->set('user_id', $userId);
    }

    /**
     * @param SessionInterface $session
     */
    public function logOut(SessionInterface $session)
    {
        $session->invalidate();
    }
}
