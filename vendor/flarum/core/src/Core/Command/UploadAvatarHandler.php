<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Exception;
use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\Validator\AvatarValidator;
use Flarum\Event\AvatarWillBeSaved;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadAvatarHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var FilesystemInterface
     */
    protected $uploadDir;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var AvatarValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param FilesystemInterface $uploadDir
     * @param Application $app
     * @param AvatarValidator $validator
     */
    public function __construct(Dispatcher $events, UserRepository $users, FilesystemInterface $uploadDir, Application $app, AvatarValidator $validator)
    {
        $this->events = $events;
        $this->users = $users;
        $this->uploadDir = $uploadDir;
        $this->app = $app;
        $this->validator = $validator;
    }

    /**
     * @param UploadAvatar $command
     * @return \Flarum\Core\User
     * @throws \Flarum\Core\Exception\PermissionDeniedException
     */
    public function handle(UploadAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        if ($actor->id !== $user->id) {
            $this->assertCan($actor, 'edit', $user);
        }

        $tmpFile = tempnam($this->app->storagePath().'/tmp', 'avatar');
        $command->file->moveTo($tmpFile);

        try {
            $file = new UploadedFile(
                $tmpFile,
                $command->file->getClientFilename(),
                $command->file->getClientMediaType(),
                $command->file->getSize(),
                $command->file->getError(),
                true
            );

            $this->validator->assertValid(['avatar' => $file]);

            $manager = new ImageManager;

            // Explicitly tell Intervention to encode the image as PNG (instead of having to guess from the extension)
            // Read exif data to orientate avatar only if EXIF extension is enabled
            if (extension_loaded('exif')) {
                $encodedImage = $manager->make($tmpFile)->orientate()->fit(100, 100)->encode('png', 100);
            } else {
                $encodedImage = $manager->make($tmpFile)->fit(100, 100)->encode('png', 100);
            }
            file_put_contents($tmpFile, $encodedImage);

            $this->events->fire(
                new AvatarWillBeSaved($user, $actor, $tmpFile)
            );

            $mount = new MountManager([
                'source' => new Filesystem(new Local(pathinfo($tmpFile, PATHINFO_DIRNAME))),
                'target' => $this->uploadDir,
            ]);

            if ($user->avatar_path && $mount->has($file = "target://$user->avatar_path")) {
                $mount->delete($file);
            }

            $uploadName = Str::lower(Str::quickRandom()).'.png';

            $user->changeAvatarPath($uploadName);

            $mount->move('source://'.pathinfo($tmpFile, PATHINFO_BASENAME), "target://$uploadName");

            $user->save();

            $this->dispatchEventsFor($user, $actor);

            return $user;
        } catch (Exception $e) {
            @unlink($tmpFile);

            throw $e;
        }
    }
}
