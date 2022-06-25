<?php

namespace ThomasFielding\Version\Exceptions;

use Exception;

class VersionDirectoryException extends Exception
{
    public $message = 'The version directory does not exist in your application';
}
