<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace Whoops\Exception;

class Formatter
{
    /**
     * Returns all basic information about the exception in a simple array
     * for further convertion to other languages
     * @param  Inspector $inspector
     * @param  bool      $shouldAddTrace
     * @return array
     */
    public static function formatExceptionAsDataArray(Inspector $inspector, $shouldAddTrace)
    {
        $exception = $inspector->getException();
        $response = [
            'type'    => get_class($exception),
            'message' => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
        ];

        if ($shouldAddTrace) {
            $frames    = $inspector->getFrames();
            $frameData = [];

            foreach ($frames as $frame) {
                /** @var Frame $frame */
                $frameData[] = [
                    'file'     => $frame->getFile(),
                    'line'     => $frame->getLine(),
                    'function' => $frame->getFunction(),
                    'class'    => $frame->getClass(),
                    'args'     => $frame->getArgs(),
                ];
            }

            $response['trace'] = $frameData;
        }

        return $response;
    }

    public static function formatExceptionPlain(Inspector $inspector)
    {
        $message = $inspector->getException()->getMessage();
        $frames = $inspector->getFrames();

        $plain = $inspector->getExceptionName();
        $plain .= ' thrown with message "';
        $plain .= $message;
        $plain .= '"'."\n\n";

        $plain .= "Stacktrace:\n";
        foreach ($frames as $i => $frame) {
            $plain .= "#". (count($frames) - $i - 1). " ";
            $plain .= $frame->getClass() ?: '';
            $plain .= $frame->getClass() && $frame->getFunction() ? ":" : "";
            $plain .= $frame->getFunction() ?: '';
            $plain .= ' in ';
            $plain .= ($frame->getFile() ?: '<#unknown>');
            $plain .= ':';
            $plain .= (int) $frame->getLine(). "\n";
        }

        return $plain;
    }
}
