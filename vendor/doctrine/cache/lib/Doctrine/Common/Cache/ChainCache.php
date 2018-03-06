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
 * Cache provider that allows to easily chain multiple cache providers
 *
 * @author Michaël Gallego <mic.gallego@gmail.com>
 */
class ChainCache extends CacheProvider
{
    /**
     * @var CacheProvider[]
     */
    private $cacheProviders = [];

    /**
     * Constructor
     *
     * @param CacheProvider[] $cacheProviders
     */
    public function __construct($cacheProviders = [])
    {
        $this->cacheProviders = $cacheProviders instanceof \Traversable
            ? iterator_to_array($cacheProviders, false)
            : array_values($cacheProviders);
    }

    /**
     * {@inheritDoc}
     */
    public function setNamespace($namespace)
    {
        parent::setNamespace($namespace);

        foreach ($this->cacheProviders as $cacheProvider) {
            $cacheProvider->setNamespace($namespace);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function doFetch($id)
    {
        foreach ($this->cacheProviders as $key => $cacheProvider) {
            if ($cacheProvider->doContains($id)) {
                $value = $cacheProvider->doFetch($id);

                // We populate all the previous cache layers (that are assumed to be faster)
                for ($subKey = $key - 1 ; $subKey >= 0 ; $subKey--) {
                    $this->cacheProviders[$subKey]->doSave($id, $value);
                }

                return $value;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        /* @var $traversedProviders CacheProvider[] */
        $traversedProviders = [];
        $keysCount          = count($keys);
        $fetchedValues      = [];

        foreach ($this->cacheProviders as $key => $cacheProvider) {
            $fetchedValues = $cacheProvider->doFetchMultiple($keys);

            // We populate all the previous cache layers (that are assumed to be faster)
            if (count($fetchedValues) === $keysCount) {
                foreach ($traversedProviders as $previousCacheProvider) {
                    $previousCacheProvider->doSaveMultiple($fetchedValues);
                }

                return $fetchedValues;
            }

            $traversedProviders[] = $cacheProvider;
        }

        return $fetchedValues;
    }

    /**
     * {@inheritDoc}
     */
    protected function doContains($id)
    {
        foreach ($this->cacheProviders as $cacheProvider) {
            if ($cacheProvider->doContains($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $stored = true;

        foreach ($this->cacheProviders as $cacheProvider) {
            $stored = $cacheProvider->doSave($id, $data, $lifeTime) && $stored;
        }

        return $stored;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0)
    {
        $stored = true;

        foreach ($this->cacheProviders as $cacheProvider) {
            $stored = $cacheProvider->doSaveMultiple($keysAndValues, $lifetime) && $stored;
        }

        return $stored;
    }

    /**
     * {@inheritDoc}
     */
    protected function doDelete($id)
    {
        $deleted = true;

        foreach ($this->cacheProviders as $cacheProvider) {
            $deleted = $cacheProvider->doDelete($id) && $deleted;
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeleteMultiple(array $keys)
    {
        $deleted = true;

        foreach ($this->cacheProviders as $cacheProvider) {
            $deleted = $cacheProvider->doDeleteMultiple($keys) && $deleted;
        }

        return $deleted;
    }

    /**
     * {@inheritDoc}
     */
    protected function doFlush()
    {
        $flushed = true;

        foreach ($this->cacheProviders as $cacheProvider) {
            $flushed = $cacheProvider->doFlush() && $flushed;
        }

        return $flushed;
    }

    /**
     * {@inheritDoc}
     */
    protected function doGetStats()
    {
        // We return all the stats from all adapters
        $stats = [];

        foreach ($this->cacheProviders as $cacheProvider) {
            $stats[] = $cacheProvider->doGetStats();
        }

        return $stats;
    }
}
