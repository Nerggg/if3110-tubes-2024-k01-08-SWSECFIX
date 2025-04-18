<?php

namespace src\exceptions;

class ConflictHttpException extends BaseHttpException
{
    public function __construct(string $message)
    {
        // sanitize
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        parent::__construct(409, $message);
    }
}
