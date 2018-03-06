<?php

declare(strict_types=1);

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

use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Model\BSONDocument;

/**
 * MongoDB cache provider for ext-mongodb
 *
 * @internal Do not use - will be removed in 2.0. Use MongoDBCache instead
 */
class ExtMongoDBCache extends CacheProvider
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var bool
     */
    private $expirationIndexCreated = false;

    /**
     * Constructor.
     *
     * This provider will default to the write concern and read preference
     * options set on the Database instance (or inherited from MongoDB or
     * Client). Using an unacknowledged write concern (< 1) may make the return
     * values of delete() and save() unreliable. Reading from secondaries may
     * make contain() and fetch() unreliable.
     *
     * @see http://www.php.net/manual/en/mongo.readpreferences.php
     * @see http://www.php.net/manual/en/mongo.writeconcerns.php
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        // Ensure there is no typemap set - we want to use our own
        $this->collection = $collection->withOptions(['typeMap' => null]);
        $this->database = new Database($collection->getManager(), $collection->getDatabaseName());
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $document = $this->collection->findOne(['_id' => $id], [MongoDBCache::DATA_FIELD, MongoDBCache::EXPIRATION_FIELD]);

        if ($document === null) {
            return false;
        }

        if ($this->isExpired($document)) {
            $this->createExpirationIndex();
            $this->doDelete($id);
            return false;
        }

        return unserialize($document[MongoDBCache::DATA_FIELD]->getData());
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $document = $this->collection->findOne(['_id' => $id], [MongoDBCache::EXPIRATION_FIELD]);

        if ($document === null) {
            return false;
        }

        if ($this->isExpired($document)) {
            $this->createExpirationIndex();
            $this->doDelete($id);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        try {
            $this->collection->updateOne(
                ['_id' => $id],
                ['$set' => [
                    MongoDBCache::EXPIRATION_FIELD => ($lifeTime > 0 ? new UTCDateTime((time() + $lifeTime) * 1000): null),
                    MongoDBCache::DATA_FIELD => new Binary(serialize($data), Binary::TYPE_GENERIC),
                ]],
                ['upsert' => true]
            );
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        try {
            $this->collection->deleteOne(['_id' => $id]);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        try {
            // Use remove() in lieu of drop() to maintain any collection indexes
            $this->collection->deleteMany([]);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $uptime = null;
        $memoryUsage = null;

        try {
            $serverStatus = $this->database->command([
                'serverStatus' => 1,
                'locks' => 0,
                'metrics' => 0,
                'recordStats' => 0,
                'repl' => 0,
            ])->toArray()[0];
            $uptime = $serverStatus['uptime'] ?? null;
        } catch (Exception $e) {
        }

        try {
            $collStats = $this->database->command(['collStats' => $this->collection->getCollectionName()])->toArray()[0];
            $memoryUsage = $collStats['size'] ?? null;
        } catch (Exception $e) {
        }

        return [
            Cache::STATS_HITS => null,
            Cache::STATS_MISSES => null,
            Cache::STATS_UPTIME => $uptime,
            Cache::STATS_MEMORY_USAGE => $memoryUsage,
            Cache::STATS_MEMORY_AVAILABLE  => null,
        ];
    }

    /**
     * Check if the document is expired.
     *
     * @param BSONDocument $document
     *
     * @return bool
     */
    private function isExpired(BSONDocument $document): bool
    {
        return isset($document[MongoDBCache::EXPIRATION_FIELD]) &&
            $document[MongoDBCache::EXPIRATION_FIELD] instanceof UTCDateTime &&
            $document[MongoDBCache::EXPIRATION_FIELD]->toDateTime() < new \DateTime();
    }

    private function createExpirationIndex(): void
    {
        if ($this->expirationIndexCreated) {
            return;
        }

        $this->collection->createIndex([MongoDBCache::EXPIRATION_FIELD => 1], ['background' => true, 'expireAfterSeconds' => 0]);
    }
}
