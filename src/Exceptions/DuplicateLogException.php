<?php

namespace ThomasFielding\Version\Exceptions;

use Exception;

class DuplicateLogException extends Exception
{
    public $message = 'A log has already been created relating to this git branch';
}