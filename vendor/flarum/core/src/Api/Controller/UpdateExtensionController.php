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
use Flarum\Extension\ExtensionManager;
use Flarum\Http\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateExtensionController implements ControllerInterface
{
    use AssertPermissionTrait;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @param ExtensionManager $extensions
     */
    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $enabled = array_get($request->getParsedBody(), 'enabled');
        $name = array_get($request->getQueryParams(), 'name');

        if ($enabled === true) {
            $this->extensions->enable($name);
        } elseif ($enabled === false) {
            $this->extensions->disable($name);
        }
    }
}
