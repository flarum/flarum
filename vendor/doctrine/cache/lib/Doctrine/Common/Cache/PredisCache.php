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

use Predis\ClientInterface;

/**
 * Predis cache provider.
 *
 * @author othillo <othillo@othillo.nl>
 */
class PredisCache extends CacheProvider
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param ClientInterface $client
     *
     * @return void
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $result = $this->client->get($id);
        if (null === $result) {
            return false;
        }

        return unserialize($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        $fetchedItems = call_user_func_array([$this->client, 'mget'], $keys);

        return array_map('unserialize', array_filter(array_combine($keys, $fetchedItems)));
    }

    /**
     * {@inheritdoc}
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0)
    {
        if ($lifetime) {
            $success = true;

            // Keys have lifetime, use SETEX for each of them
            foreach ($keysAndValues as $key => $value) {
                $response = $this->client->setex($key, $lifetime, serialize($value));

                if ((string) $response != 'OK') {
                    $success = false;
                }
            }

            return $success;
        }

        // No lifetime, use MSET
        $response = $this->client->mset(array_map(function ($value) {
            return serialize($value);
        }, $keysAndValues));

        return (string) $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return (bool) $this->client->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $data = serialize($data);
        if ($lifeTime > 0) {
            $response = $this->client->setex($id, $lifeTime, $data);
        } else {
            $response = $this->client->set($id, $data);
        }

        return $response === true || $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->client->del($id) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeleteMultiple(array $keys)
    {
        return $this->client->del($keys) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $response = $this->client->flushdb();

        return $response === true || $response == 'OK';
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $info = $this->client->info();

        return [
            Cache::STATS_HITS              => $info['Stats']['keyspace_hits'],
            Cache::STATS_MISSES            => $info['Stats']['keyspace_misses'],
            Cache::STATS_UPTIME            => $info['Server']['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE      => $info['Memory']['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE  => false
        ];
    }
}
