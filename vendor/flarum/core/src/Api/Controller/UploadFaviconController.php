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

class UploadFaviconController extends ShowForumController
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

        $file = array_get($request->getUploadedFiles(), 'favicon');

        $tmpFile = tempnam($this->app->storagePath().'/tmp', 'favicon');
        $file->moveTo($tmpFile);

        $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if ($extension !== 'ico') {
            $manager = new ImageManager;

            $encodedImage = $manager->make($tmpFile)->resize(64, 64, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode('png');
            file_put_contents($tmpFile, $encodedImage);

            $extension = 'png';
        }

        $mount = new MountManager([
            'source' => new Filesystem(new Local(pathinfo($tmpFile, PATHINFO_DIRNAME))),
            'target' => new Filesystem(new Local($this->app->publicPath().'/assets')),
        ]);

        if (($path = $this->settings->get('favicon_path')) && $mount->has($file = "target://$path")) {
            $mount->delete($file);
        }

        $uploadName = 'favicon-'.Str::lower(Str::quickRandom(8)).'.'.$extension;

        $mount->move('source://'.pathinfo($tmpFile, PATHINFO_BASENAME), "target://$uploadName");

        $this->settings->set('favicon_path', $uploadName);

        return parent::data($request, $document);
    }
}
