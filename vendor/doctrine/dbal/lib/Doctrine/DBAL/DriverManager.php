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

namespace Doctrine\DBAL;

use Doctrine\Common\EventManager;

/**
 * Factory for creating Doctrine\DBAL\Connection instances.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
final class DriverManager
{
    /**
     * List of supported drivers and their mappings to the driver classes.
     *
     * To add your own driver use the 'driverClass' parameter to
     * {@link DriverManager::getConnection()}.
     *
     * @var array
     */
     private static $_driverMap = array(
         'pdo_mysql'          => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
         'pdo_sqlite'         => 'Doctrine\DBAL\Driver\PDOSqlite\Driver',
         'pdo_pgsql'          => 'Doctrine\DBAL\Driver\PDOPgSql\Driver',
         'pdo_oci'            => 'Doctrine\DBAL\Driver\PDOOracle\Driver',
         'oci8'               => 'Doctrine\DBAL\Driver\OCI8\Driver',
         'ibm_db2'            => 'Doctrine\DBAL\Driver\IBMDB2\DB2Driver',
         'pdo_sqlsrv'         => 'Doctrine\DBAL\Driver\PDOSqlsrv\Driver',
         'mysqli'             => 'Doctrine\DBAL\Driver\Mysqli\Driver',
         'drizzle_pdo_mysql'  => 'Doctrine\DBAL\Driver\DrizzlePDOMySql\Driver',
         'sqlanywhere'        => 'Doctrine\DBAL\Driver\SQLAnywhere\Driver',
         'sqlsrv'             => 'Doctrine\DBAL\Driver\SQLSrv\Driver',
    );

    /**
     * List of URL schemes from a database URL and their mappings to driver.
     */
    private static $driverSchemeAliases = array(
        'db2'        => 'ibm_db2',
        'mssql'      => 'pdo_sqlsrv',
        'mysql'      => 'pdo_mysql',
        'mysql2'     => 'pdo_mysql', // Amazon RDS, for some weird reason
        'postgres'   => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
        'pgsql'      => 'pdo_pgsql',
        'sqlite'     => 'pdo_sqlite',
        'sqlite3'    => 'pdo_sqlite',
    );

    /**
     * Private constructor. This class cannot be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Creates a connection object based on the specified parameters.
     * This method returns a Doctrine\DBAL\Connection which wraps the underlying
     * driver connection.
     *
     * $params must contain at least one of the following.
     *
     * Either 'driver' with one of the following values:
     *
     *     pdo_mysql
     *     pdo_sqlite
     *     pdo_pgsql
     *     pdo_oci (unstable)
     *     pdo_sqlsrv
     *     pdo_sqlsrv
     *     mysqli
     *     sqlanywhere
     *     sqlsrv
     *     ibm_db2 (unstable)
     *     drizzle_pdo_mysql
     *
     * OR 'driverClass' that contains the full class name (with namespace) of the
     * driver class to instantiate.
     *
     * Other (optional) parameters:
     *
     * <b>user (string)</b>:
     * The username to use when connecting.
     *
     * <b>password (string)</b>:
     * The password to use when connecting.
     *
     * <b>driverOptions (array)</b>:
     * Any additional driver-specific options for the driver. These are just passed
     * through to the driver.
     *
     * <b>pdo</b>:
     * You can pass an existing PDO instance through this parameter. The PDO
     * instance will be wrapped in a Doctrine\DBAL\Connection.
     *
     * <b>wrapperClass</b>:
     * You may specify a custom wrapper class through the 'wrapperClass'
     * parameter but this class MUST inherit from Doctrine\DBAL\Connection.
     *
     * <b>driverClass</b>:
     * The driver class to use.
     *
     * @param array                              $params       The parameters.
     * @param \Doctrine\DBAL\Configuration|null  $config       The configuration to use.
     * @param \Doctrine\Common\EventManager|null $eventManager The event manager to use.
     *
     * @return \Doctrine\DBAL\Connection
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getConnection(
            array $params,
            Configuration $config = null,
            EventManager $eventManager = null)
    {
        // create default config and event manager, if not set
        if ( ! $config) {
            $config = new Configuration();
        }
        if ( ! $eventManager) {
            $eventManager = new EventManager();
        }

        $params = self::parseDatabaseUrl($params);

        // check for existing pdo object
        if (isset($params['pdo']) && ! $params['pdo'] instanceof \PDO) {
            throw DBALException::invalidPdoInstance();
        } elseif (isset($params['pdo'])) {
            $params['pdo']->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $params['driver'] = 'pdo_' . $params['pdo']->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } else {
            self::_checkParams($params);
        }
        if (isset($params['driverClass'])) {
            $className = $params['driverClass'];
        } else {
            $className = self::$_driverMap[$params['driver']];
        }

        $driver = new $className();

        $wrapperClass = 'Doctrine\DBAL\Connection';
        if (isset($params['wrapperClass'])) {
            if (is_subclass_of($params['wrapperClass'], $wrapperClass)) {
               $wrapperClass = $params['wrapperClass'];
            } else {
                throw DBALException::invalidWrapperClass($params['wrapperClass']);
            }
        }

        return new $wrapperClass($params, $driver, $config, $eventManager);
    }

    /**
     * Returns the list of supported drivers.
     *
     * @return array
     */
    public static function getAvailableDrivers()
    {
        return array_keys(self::$_driverMap);
    }

    /**
     * Checks the list of parameters.
     *
     * @param array $params The list of parameters.
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function _checkParams(array $params)
    {
        // check existence of mandatory parameters

        // driver
        if ( ! isset($params['driver']) && ! isset($params['driverClass'])) {
            throw DBALException::driverRequired();
        }

        // check validity of parameters

        // driver
        if (isset($params['driver']) && ! isset(self::$_driverMap[$params['driver']])) {
            throw DBALException::unknownDriver($params['driver'], array_keys(self::$_driverMap));
        }

        if (isset($params['driverClass']) && ! in_array('Doctrine\DBAL\Driver', class_implements($params['driverClass'], true))) {
            throw DBALException::invalidDriverClass($params['driverClass']);
        }
    }

    /**
     * Normalizes the given connection URL path.
     *
     * @param string $urlPath
     *
     * @return string The normalized connection URL path
     */
    private static function normalizeDatabaseUrlPath($urlPath)
    {
        // Trim leading slash from URL path.
        return substr($urlPath, 1);
    }

    /**
     * Extracts parts from a database URL, if present, and returns an
     * updated list of parameters.
     *
     * @param array $params The list of parameters.
     *
     * @return array A modified list of parameters with info from a database
     *               URL extracted into indidivual parameter parts.
     *
     * @throws DBALException
     */
    private static function parseDatabaseUrl(array $params)
    {
        if (!isset($params['url'])) {
            return $params;
        }

        // (pdo_)?sqlite3?:///... => (pdo_)?sqlite3?://localhost/... or else the URL will be invalid
        $url = preg_replace('#^((?:pdo_)?sqlite3?):///#', '$1://localhost/', $params['url']);

        // PHP < 5.4.8 doesn't parse schemeless urls properly.
        // See: https://php.net/parse-url#refsect1-function.parse-url-changelog
        if (PHP_VERSION_ID < 50408 && strpos($url, '//') === 0) {
            $url = parse_url('fake:' . $url);

            unset($url['scheme']);
        } else {
            $url = parse_url($url);
        }

        if ($url === false) {
            throw new DBALException('Malformed parameter "url".');
        }

        $url = array_map('rawurldecode', $url);

        // If we have a connection URL, we have to unset the default PDO instance connection parameter (if any)
        // as we cannot merge connection details from the URL into the PDO instance (URL takes precedence).
        unset($params['pdo']);

        $params = self::parseDatabaseUrlScheme($url, $params);

        if (isset($url['host'])) {
            $params['host'] = $url['host'];
        }
        if (isset($url['port'])) {
            $params['port'] = $url['port'];
        }
        if (isset($url['user'])) {
            $params['user'] = $url['user'];
        }
        if (isset($url['pass'])) {
            $params['password'] = $url['pass'];
        }

        $params = self::parseDatabaseUrlPath($url, $params);
        $params = self::parseDatabaseUrlQuery($url, $params);

        return $params;
    }

    /**
     * Parses the given connection URL and resolves the given connection parameters.
     *
     * Assumes that the connection URL scheme is already parsed and resolved into the given connection parameters
     * via {@link parseDatabaseUrlScheme}.
     *
     * @param array $url    The URL parts to evaluate.
     * @param array $params The connection parameters to resolve.
     *
     * @return array The resolved connection parameters.
     *
     * @see parseDatabaseUrlScheme
     */
    private static function parseDatabaseUrlPath(array $url, array $params)
    {
        if (! isset($url['path'])) {
            return $params;
        }

        $url['path'] = self::normalizeDatabaseUrlPath($url['path']);

        // If we do not have a known DBAL driver, we do not know any connection URL path semantics to evaluate
        // and therefore treat the path as regular DBAL connection URL path.
        if (! isset($params['driver'])) {
            return self::parseRegularDatabaseUrlPath($url, $params);
        }

        if (strpos($params['driver'], 'sqlite') !== false) {
            return self::parseSqliteDatabaseUrlPath($url, $params);
        }

        return self::parseRegularDatabaseUrlPath($url, $params);
    }

    /**
     * Parses the query part of the given connection URL and resolves the given connection parameters.
     *
     * @param array $url    The connection URL parts to evaluate.
     * @param array $params The connection parameters to resolve.
     *
     * @return array The resolved connection parameters.
     */
    private static function parseDatabaseUrlQuery(array $url, array $params)
    {
        if (! isset($url['query'])) {
            return $params;
        }

        $query = array();

        parse_str($url['query'], $query); // simply ingest query as extra params, e.g. charset or sslmode

        return array_merge($params, $query); // parse_str wipes existing array elements
    }

    /**
     * Parses the given regular connection URL and resolves the given connection parameters.
     *
     * Assumes that the "path" URL part is already normalized via {@link normalizeDatabaseUrlPath}.
     *
     * @param array $url    The regular connection URL parts to evaluate.
     * @param array $params The connection parameters to resolve.
     *
     * @return array The resolved connection parameters.
     *
     * @see normalizeDatabaseUrlPath
     */
    private static function parseRegularDatabaseUrlPath(array $url, array $params)
    {
        $params['dbname'] = $url['path'];

        return $params;
    }

    /**
     * Parses the given SQLite connection URL and resolves the given connection parameters.
     *
     * Assumes that the "path" URL part is already normalized via {@link normalizeDatabaseUrlPath}.
     *
     * @param array $url    The SQLite connection URL parts to evaluate.
     * @param array $params The connection parameters to resolve.
     *
     * @return array The resolved connection parameters.
     *
     * @see normalizeDatabaseUrlPath
     */
    private static function parseSqliteDatabaseUrlPath(array $url, array $params)
    {
        if ($url['path'] === ':memory:') {
            $params['memory'] = true;

            return $params;
        }

        $params['path'] = $url['path']; // pdo_sqlite driver uses 'path' instead of 'dbname' key

        return $params;
    }

    /**
     * Parses the scheme part from given connection URL and resolves the given connection parameters.
     *
     * @param array $url    The connection URL parts to evaluate.
     * @param array $params The connection parameters to resolve.
     *
     * @return array The resolved connection parameters.
     *
     * @throws DBALException if parsing failed or resolution is not possible.
     */
    private static function parseDatabaseUrlScheme(array $url, array $params)
    {
        if (isset($url['scheme'])) {
            // The requested driver from the URL scheme takes precedence
            // over the default custom driver from the connection parameters (if any).
            unset($params['driverClass']);

            // URL schemes must not contain underscores, but dashes are ok
            $driver = str_replace('-', '_', $url['scheme']);

            // The requested driver from the URL scheme takes precedence
            // over the default driver from the connection parameters (if any).
            $params['driver'] = isset(self::$driverSchemeAliases[$driver])
                // use alias like "postgres", else we just let checkParams decide later
                // if the driver exists (for literal "pdo-pgsql" etc)
                ? self::$driverSchemeAliases[$driver]
                : $driver;

            return $params;
        }

        // If a schemeless connection URL is given, we require a default driver or default custom driver
        // as connection parameter.
        if (! isset($params['driverClass']) && ! isset($params['driver'])) {
            throw DBALException::driverRequired($params['url']);
        }

        return $params;
    }
}
