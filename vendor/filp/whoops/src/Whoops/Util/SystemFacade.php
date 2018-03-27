<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace Whoops\Util;

class SystemFacade
{
    /**
     * Turns on output buffering.
     *
     * @return bool
     */
    public function startOutputBuffering()
    {
        return ob_start();
    }

    /**
     * @param callable $handler
     * @param int      $types
     *
     * @return callable|null
     */
    public function setErrorHandler(callable $handler, $types = 'use-php-defaults')
    {
        // Workaround for PHP 5.5
        if ($types === 'use-php-defaults') {
            $types = E_ALL | E_STRICT;
        }
        return set_error_handler($handler, $types);
    }

    /**
     * @param callable $handler
     *
     * @return callable|null
     */
    public function setExceptionHandler(callable $handler)
    {
        return set_exception_handler($handler);
    }

    /**
     * @return void
     */
    public function restoreExceptionHandler()
    {
        restore_exception_handler();
    }

    /**
     * @return void
     */
    public function restoreErrorHandler()
    {
        restore_error_handler();
    }

    /**
     * @param callable $function
     *
     * @return void
     */
    public function registerShutdownFunction(callable $function)
    {
        register_shutdown_function($function);
    }

    /**
     * @return string|false
     */
    public function cleanOutputBuffer()
    {
        return ob_get_clean();
    }

    /**
     * @return int
     */
    public function getOutputBufferLevel()
    {
        return ob_get_level();
    }

    /**
     * @return bool
     */
    public function endOutputBuffering()
    {
        return ob_end_clean();
    }

    /**
     * @return void
     */
    public function flushOutputBuffer()
    {
        flush();
    }

    /**
     * @return int
     */
    public function getErrorReportingLevel()
    {
        return error_reporting();
    }

    /**
     * @return array|null
     */
    public function getLastError()
    {
        return error_get_last();
    }

    /**
     * @param int $httpCode
     *
     * @return int
     */
    public function setHttpResponseCode($httpCode)
    {
        return http_response_code($httpCode);
    }

    /**
     * @param int $exitStatus
     */
    public function stopExecution($exitStatus)
    {
        exit($exitStatus);
    }
}
