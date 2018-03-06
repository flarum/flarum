<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace Whoops;

use InvalidArgumentException;
use Whoops\Exception\ErrorException;
use Whoops\Exception\Inspector;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\Handler;
use Whoops\Handler\HandlerInterface;
use Whoops\Util\Misc;
use Whoops\Util\SystemFacade;

final class Run implements RunInterface
{
    private $isRegistered;
    private $allowQuit       = true;
    private $sendOutput      = true;

    /**
     * @var integer|false
     */
    private $sendHttpCode    = 500;

    /**
     * @var HandlerInterface[]
     */
    private $handlerStack = [];

    private $silencedPatterns = [];

    private $system;

    public function __construct(SystemFacade $system = null)
    {
        $this->system = $system ?: new SystemFacade;
    }

    /**
     * Pushes a handler to the end of the stack
     *
     * @throws InvalidArgumentException  If argument is not callable or instance of HandlerInterface
     * @param  Callable|HandlerInterface $handler
     * @return Run
     */
    public function pushHandler($handler)
    {
        if (is_callable($handler)) {
            $handler = new CallbackHandler($handler);
        }

        if (!$handler instanceof HandlerInterface) {
            throw new InvalidArgumentException(
                  "Argument to " . __METHOD__ . " must be a callable, or instance of "
                . "Whoops\\Handler\\HandlerInterface"
            );
        }

        $this->handlerStack[] = $handler;
        return $this;
    }

    /**
     * Removes the last handler in the stack and returns it.
     * Returns null if there"s nothing else to pop.
     * @return null|HandlerInterface
     */
    public function popHandler()
    {
        return array_pop($this->handlerStack);
    }

    /**
     * Returns an array with all handlers, in the
     * order they were added to the stack.
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlerStack;
    }

    /**
     * Clears all handlers in the handlerStack, including
     * the default PrettyPage handler.
     * @return Run
     */
    public function clearHandlers()
    {
        $this->handlerStack = [];
        return $this;
    }

    /**
     * @param  \Throwable $exception
     * @return Inspector
     */
    private function getInspector($exception)
    {
        return new Inspector($exception);
    }

    /**
     * Registers this instance as an error handler.
     * @return Run
     */
    public function register()
    {
        if (!$this->isRegistered) {
            // Workaround PHP bug 42098
            // https://bugs.php.net/bug.php?id=42098
            class_exists("\\Whoops\\Exception\\ErrorException");
            class_exists("\\Whoops\\Exception\\FrameCollection");
            class_exists("\\Whoops\\Exception\\Frame");
            class_exists("\\Whoops\\Exception\\Inspector");

            $this->system->setErrorHandler([$this, self::ERROR_HANDLER]);
            $this->system->setExceptionHandler([$this, self::EXCEPTION_HANDLER]);
            $this->system->registerShutdownFunction([$this, self::SHUTDOWN_HANDLER]);

            $this->isRegistered = true;
        }

        return $this;
    }

    /**
     * Unregisters all handlers registered by this Whoops\Run instance
     * @return Run
     */
    public function unregister()
    {
        if ($this->isRegistered) {
            $this->system->restoreExceptionHandler();
            $this->system->restoreErrorHandler();

            $this->isRegistered = false;
        }

        return $this;
    }

    /**
     * Should Whoops allow Handlers to force the script to quit?
     * @param  bool|int $exit
     * @return bool
     */
    public function allowQuit($exit = null)
    {
        if (func_num_args() == 0) {
            return $this->allowQuit;
        }

        return $this->allowQuit = (bool) $exit;
    }

    /**
     * Silence particular errors in particular files
     * @param  array|string $patterns List or a single regex pattern to match
     * @param  int          $levels   Defaults to E_STRICT | E_DEPRECATED
     * @return \Whoops\Run
     */
    public function silenceErrorsInPaths($patterns, $levels = 10240)
    {
        $this->silencedPatterns = array_merge(
            $this->silencedPatterns,
            array_map(
                function ($pattern) use ($levels) {
                    return [
                        "pattern" => $pattern,
                        "levels" => $levels,
                    ];
                },
                (array) $patterns
            )
        );
        return $this;
    }


    /**
     * Returns an array with silent errors in path configuration
     *
     * @return array
     */
    public function getSilenceErrorsInPaths()
    {
        return $this->silencedPatterns;
    }

    /*
     * Should Whoops send HTTP error code to the browser if possible?
     * Whoops will by default send HTTP code 500, but you may wish to
     * use 502, 503, or another 5xx family code.
     *
     * @param bool|int $code
     * @return int|false
     */
    public function sendHttpCode($code = null)
    {
        if (func_num_args() == 0) {
            return $this->sendHttpCode;
        }

        if (!$code) {
            return $this->sendHttpCode = false;
        }

        if ($code === true) {
            $code = 500;
        }

        if ($code < 400 || 600 <= $code) {
            throw new InvalidArgumentException(
                 "Invalid status code '$code', must be 4xx or 5xx"
            );
        }

        return $this->sendHttpCode = $code;
    }

    /**
     * Should Whoops push output directly to the client?
     * If this is false, output will be returned by handleException
     * @param  bool|int $send
     * @return bool
     */
    public function writeToOutput($send = null)
    {
        if (func_num_args() == 0) {
            return $this->sendOutput;
        }

        return $this->sendOutput = (bool) $send;
    }

    /**
     * Handles an exception, ultimately generating a Whoops error
     * page.
     *
     * @param  \Throwable $exception
     * @return string     Output generated by handlers
     */
    public function handleException($exception)
    {
        // Walk the registered handlers in the reverse order
        // they were registered, and pass off the exception
        $inspector = $this->getInspector($exception);

        // Capture output produced while handling the exception,
        // we might want to send it straight away to the client,
        // or return it silently.
        $this->system->startOutputBuffering();

        // Just in case there are no handlers:
        $handlerResponse = null;
        $handlerContentType = null;

        foreach (array_reverse($this->handlerStack) as $handler) {
            $handler->setRun($this);
            $handler->setInspector($inspector);
            $handler->setException($exception);

            // The HandlerInterface does not require an Exception passed to handle()
            // and neither of our bundled handlers use it.
            // However, 3rd party handlers may have already relied on this parameter,
            // and removing it would be possibly breaking for users.
            $handlerResponse = $handler->handle($exception);

            // Collect the content type for possible sending in the headers.
            $handlerContentType = method_exists($handler, 'contentType') ? $handler->contentType() : null;

            if (in_array($handlerResponse, [Handler::LAST_HANDLER, Handler::QUIT])) {
                // The Handler has handled the exception in some way, and
                // wishes to quit execution (Handler::QUIT), or skip any
                // other handlers (Handler::LAST_HANDLER). If $this->allowQuit
                // is false, Handler::QUIT behaves like Handler::LAST_HANDLER
                break;
            }
        }

        $willQuit = $handlerResponse == Handler::QUIT && $this->allowQuit();

        $output = $this->system->cleanOutputBuffer();

        // If we're allowed to, send output generated by handlers directly
        // to the output, otherwise, and if the script doesn't quit, return
        // it so that it may be used by the caller
        if ($this->writeToOutput()) {
            // @todo Might be able to clean this up a bit better
            if ($willQuit) {
                // Cleanup all other output buffers before sending our output:
                while ($this->system->getOutputBufferLevel() > 0) {
                    $this->system->endOutputBuffering();
                }

                // Send any headers if needed:
                if (Misc::canSendHeaders() && $handlerContentType) {
                    header("Content-Type: {$handlerContentType}");
                }
            }

            $this->writeToOutputNow($output);
        }

        if ($willQuit) {
            // HHVM fix for https://github.com/facebook/hhvm/issues/4055
            $this->system->flushOutputBuffer();

            $this->system->stopExecution(1);
        }

        return $output;
    }

    /**
     * Converts generic PHP errors to \ErrorException
     * instances, before passing them off to be handled.
     *
     * This method MUST be compatible with set_error_handler.
     *
     * @param int    $level
     * @param string $message
     * @param string $file
     * @param int    $line
     *
     * @return bool
     * @throws ErrorException
     */
    public function handleError($level, $message, $file = null, $line = null)
    {
        if ($level & $this->system->getErrorReportingLevel()) {
            foreach ($this->silencedPatterns as $entry) {
                $pathMatches = (bool) preg_match($entry["pattern"], $file);
                $levelMatches = $level & $entry["levels"];
                if ($pathMatches && $levelMatches) {
                    // Ignore the error, abort handling
                    // See https://github.com/filp/whoops/issues/418
                    return true;
                }
            }

            // XXX we pass $level for the "code" param only for BC reasons.
            // see https://github.com/filp/whoops/issues/267
            $exception = new ErrorException($message, /*code*/ $level, /*severity*/ $level, $file, $line);
            if ($this->canThrowExceptions) {
                throw $exception;
            } else {
                $this->handleException($exception);
            }
            // Do not propagate errors which were already handled by Whoops.
            return true;
        }

        // Propagate error to the next handler, allows error_get_last() to
        // work on silenced errors.
        return false;
    }

    /**
     * Special case to deal with Fatal errors and the like.
     */
    public function handleShutdown()
    {
        // If we reached this step, we are in shutdown handler.
        // An exception thrown in a shutdown handler will not be propagated
        // to the exception handler. Pass that information along.
        $this->canThrowExceptions = false;

        $error = $this->system->getLastError();
        if ($error && Misc::isLevelFatal($error['type'])) {
            // If there was a fatal error,
            // it was not handled in handleError yet.
            $this->handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }

    /**
     * In certain scenarios, like in shutdown handler, we can not throw exceptions
     * @var bool
     */
    private $canThrowExceptions = true;

    /**
     * Echo something to the browser
     * @param  string $output
     * @return $this
     */
    private function writeToOutputNow($output)
    {
        if ($this->sendHttpCode() && \Whoops\Util\Misc::canSendHeaders()) {
            $this->system->setHttpResponseCode(
                $this->sendHttpCode()
            );
        }

        echo $output;

        return $this;
    }
}
