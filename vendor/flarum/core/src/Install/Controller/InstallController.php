<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Controller;

use Exception;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\SessionAuthenticator;
use Flarum\Install\Console\DefaultsDataProvider;
use Flarum\Install\Console\InstallCommand;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;

class InstallController implements ControllerInterface
{
    protected $command;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * InstallController constructor.
     * @param InstallCommand $command
     * @param SessionAuthenticator $authenticator
     */
    public function __construct(InstallCommand $command, SessionAuthenticator $authenticator)
    {
        $this->command = $command;
        $this->authenticator = $authenticator;
    }

    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request)
    {
        $input = $request->getParsedBody();

        $data = new DefaultsDataProvider;

        $host = array_get($input, 'mysqlHost');
        $port = '3306';

        if (str_contains($host, ':')) {
            list($host, $port) = explode(':', $host, 2);
        }

        $data->setDatabaseConfiguration([
            'driver'   => 'mysql',
            'host'     => $host,
            'database' => array_get($input, 'mysqlDatabase'),
            'username' => array_get($input, 'mysqlUsername'),
            'password' => array_get($input, 'mysqlPassword'),
            'prefix'   => array_get($input, 'tablePrefix'),
            'port'     => $port,
        ]);

        $data->setAdminUser([
            'username'              => array_get($input, 'adminUsername'),
            'password'              => array_get($input, 'adminPassword'),
            'password_confirmation' => array_get($input, 'adminPasswordConfirmation'),
            'email'                 => array_get($input, 'adminEmail'),
        ]);

        $baseUrl = rtrim((string) $request->getAttribute('originalUri'), '/');
        $data->setBaseUrl($baseUrl);

        $data->setSetting('forum_title', array_get($input, 'forumTitle'));
        $data->setSetting('mail_from', 'noreply@'.preg_replace('/^www\./i', '', parse_url($baseUrl, PHP_URL_HOST)));
        $data->setSetting('welcome_title', 'Welcome to '.array_get($input, 'forumTitle'));

        $body = fopen('php://temp', 'wb+');
        $input = new StringInput('');
        $output = new StreamOutput($body);

        $this->command->setDataSource($data);

        try {
            $this->command->run($input, $output);
        } catch (Exception $e) {
            return new HtmlResponse($e->getMessage(), 500);
        }

        $session = $request->getAttribute('session');
        $this->authenticator->logIn($session, 1);

        return new Response($body);
    }
}
