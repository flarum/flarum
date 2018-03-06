<?php

namespace Studio\Parts;

use Symfony\Component\Console\Style\StyleInterface;

class ConsoleInput implements PartInputInterface
{
    /**
     * @var StyleInterface
     */
    protected $output;


    public function __construct(StyleInterface $output)
    {
        $this->output = $output;
    }

    public function confirm($question, $default = false)
    {
        return $this->output->confirm(
            "<question>$question</question> ",
            $default
        );
    }

    public function ask($question, $regex, $errorText = null, $default = null)
    {
        return $this->output->ask(
            "<question>$question</question>",
            $default,
            $this->validateWith($regex, $errorText)
        );
    }

    protected function validateWith($regex, $errorText)
    {
        if (!$errorText) $errorText = 'Invalid. Please try again.';

        return function ($answer) use ($regex, $errorText) {
            if (preg_match($regex, $answer)) return $answer;

            throw new \RuntimeException($errorText);
        };
    }
}
