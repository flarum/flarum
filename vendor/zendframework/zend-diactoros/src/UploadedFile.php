<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFile implements UploadedFileInterface
{
    const ERROR_MESSAGES = [
        UPLOAD_ERR_OK         => 'There is no error, the file uploaded with success',
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was '
            . 'specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];

    /**
     * @var string|null
     */
    private $clientFilename;

    /**
     * @var string|null
     */
    private $clientMediaType;

    /**
     * @var int
     */
    private $error;

    /**
     * @var null|string
     */
    private $file;

    /**
     * @var bool
     */
    private $moved = false;

    /**
     * @var int
     */
    private $size;

    /**
     * @var null|StreamInterface
     */
    private $stream;

    /**
     * @param string|resource $streamOrFile
     * @param int $size
     * @param int $errorStatus
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     * @throws InvalidArgumentException
     */
    public function __construct($streamOrFile, $size, $errorStatus, $clientFilename = null, $clientMediaType = null)
    {
        if ($errorStatus === UPLOAD_ERR_OK) {
            if (is_string($streamOrFile)) {
                $this->file = $streamOrFile;
            }
            if (is_resource($streamOrFile)) {
                $this->stream = new Stream($streamOrFile);
            }

            if (! $this->file && ! $this->stream) {
                if (! $streamOrFile instanceof StreamInterface) {
                    throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
                }
                $this->stream = $streamOrFile;
            }
        }

        if (! is_int($size)) {
            throw new InvalidArgumentException('Invalid size provided for UploadedFile; must be an int');
        }
        $this->size = $size;

        if (! is_int($errorStatus)
            || 0 > $errorStatus
            || 8 < $errorStatus
        ) {
            throw new InvalidArgumentException(
                'Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant'
            );
        }
        $this->error = $errorStatus;

        if (null !== $clientFilename && ! is_string($clientFilename)) {
            throw new InvalidArgumentException(
                'Invalid client filename provided for UploadedFile; must be null or a string'
            );
        }
        $this->clientFilename = $clientFilename;

        if (null !== $clientMediaType && ! is_string($clientMediaType)) {
            throw new InvalidArgumentException(
                'Invalid client media type provided for UploadedFile; must be null or a string'
            );
        }
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException if the upload was not successful.
     */
    public function getStream()
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(sprintf(
                'Cannot retrieve stream due to upload error: %s',
                self::ERROR_MESSAGES[$this->error]
            ));
        }

        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream($this->file);
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \RuntimeException if the upload was not successful.
     * @throws \InvalidArgumentException if the $path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new RuntimeException('Cannot move file; already moved!');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(sprintf(
                'Cannot retrieve stream due to upload error: %s',
                self::ERROR_MESSAGES[$this->error]
            ));
        }

        if (! is_string($targetPath) || empty($targetPath)) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }

        $targetDirectory = dirname($targetPath);
        if (! is_dir($targetDirectory) || ! is_writable($targetDirectory)) {
            throw new RuntimeException(sprintf(
                'The target directory `%s` does not exists or is not writable',
                $targetDirectory
            ));
        }

        $sapi = PHP_SAPI;
        switch (true) {
            case (empty($sapi) || 0 === strpos($sapi, 'cli') || ! $this->file):
                // Non-SAPI environment, or no filename present
                $this->writeFile($targetPath);
                break;
            default:
                // SAPI environment, with file present
                if (false === move_uploaded_file($this->file, $targetPath)) {
                    throw new RuntimeException('Error occurred while moving uploaded file');
                }
                break;
        }

        $this->moved = true;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * Write internal stream to given path
     *
     * @param string $path
     */
    private function writeFile($path)
    {
        $handle = fopen($path, 'wb+');
        if (false === $handle) {
            throw new RuntimeException('Unable to write to designated path');
        }

        $stream = $this->getStream();
        $stream->rewind();
        while (! $stream->eof()) {
            fwrite($handle, $stream->read(4096));
        }

        fclose($handle);
    }
}
