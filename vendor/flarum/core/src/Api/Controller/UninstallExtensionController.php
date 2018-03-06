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
use Psr\Http\Message\ServerRequestInterface;

class UninstallExtensionController extends AbstractDeleteController
{
    use AssertPermissionTrait;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @param \Flarum\Extension\ExtensionManager $extensions
     */
    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    protected function delete(ServerRequestInterface $request)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $name = array_get($request->getQueryParams(), 'name');

        $extension = $this->extensions->getExtension($name);

        $this->extensions->disable($extension);
        $this->extensions->uninstall($extension);
    }
}
