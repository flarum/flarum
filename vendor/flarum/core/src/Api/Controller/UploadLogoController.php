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
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UploadLogoController extends ShowForumController
{
    use AssertPermissionTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings, Application $app)
    {
        $this->settings = $settings;
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function data(ServerRequestInterface $request, Document $document)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $file = array_get($request->getUploadedFiles(), 'logo');

        $tmpFile = tempnam($this->app->storagePath().'/tmp', 'logo');
        $file->moveTo($tmpFile);

        $manager = new ImageManager;

        $encodedImage = $manager->make($tmpFile)->heighten(60, function ($constraint) {
            $constraint->upsize();
        })->encode('png');
        file_put_contents($tmpFile, $encodedImage);

        $mount = new MountManager([
            'source' => new Filesystem(new Local(pathinfo($tmpFile, PATHINFO_DIRNAME))),
            'target' => new Filesystem(new Local($this->app->publicPath().'/assets')),
        ]);

        if (($path = $this->settings->get('logo_path')) && $mount->has($file = "target://$path")) {
            $mount->delete($file);
        }

        $uploadName = 'logo-'.Str::lower(Str::quickRandom(8)).'.png';

        $mount->move('source://'.pathinfo($tmpFile, PATHINFO_BASENAME), "target://$uploadName");

        $this->settings->set('logo_path', $uploadName);

        return parent::data($request, $document);
    }
}
