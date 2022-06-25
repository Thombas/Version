<?php

namespace ThomasFielding\Version\Exceptions;

use Exception;

class UncommittedBranchException extends Exception
{
    public $message = 'You need to commit your current git branch before creating a log';
}
