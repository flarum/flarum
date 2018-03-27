<?php

namespace Studio\Parts;

interface PartInputInterface
{
    public function confirm($question);

    public function ask($question, $regex, $errorText = null, $default = null);
}
