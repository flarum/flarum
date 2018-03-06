<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Cache;

/**
 * Php file cache driver.
 *
 * @since  2.3
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class PhpFileCache extends FileCache
{
    const EXTENSION = '.doctrinecache.php';

    /**
     * @var callable
     *
     * This is cached in a local static variable to avoid instantiating a closure each time we need an empty handler
     */
    private static $emptyErrorHandler;

    /**
     * {@inheritdoc}
     */
    public function __construct($directory, $extension = self::EXTENSION, $umask = 0002)
    {
        parent::__construct($directory, $extension, $umask);

        self::$emptyErrorHandler = function () {
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $value = $this->includeFileForId($id);

        if ($value === null) {
            return false;
        }

        if ($value['lifetime'] !== 0 && $value['lifetime'] < time()) {
            return false;
        }

        return $value['data'];
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $value = $this->includeFileForId($id);

        if ($value === null) {
            return false;
        }

        return $value['lifetime'] === 0 || $value['lifetime'] > time();
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            $lifeTime = time() + $lifeTime;
        }

        $filename  = $this->getFilename($id);

        $value = [
            'lifetime'  => $lifeTime,
            'data'      => $data
        ];

        if (is_object($data) && method_exists($data, '__set_state')) {
            $value  = var_export($value, true);
            $code   = sprintf('<?php return %s;', $value);
        } else {
            $value  = var_export(serialize($value), true);
            $code   = sprintf('<?php return unserialize(%s);', $value);
        }

        return $this->writeFile($filename, $code);
    }

    /**
     * @param string $id
     *
     * @return array|null
     */
    private function includeFileForId(string $id) : ?array
    {
        $fileName = $this->getFilename($id);

        // note: error suppression is still faster than `file_exists`, `is_file` and `is_readable`
        set_error_handler(self::$emptyErrorHandler);

        $value = include $fileName;

        restore_error_handler();

        if (! isset($value['lifetime'])) {
            return null;
        }

        return $value;
    }
}
