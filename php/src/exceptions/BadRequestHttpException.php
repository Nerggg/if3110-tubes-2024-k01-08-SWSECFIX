<?php

namespace src\exceptions;

class BadRequestHttpException extends BaseHttpException
{
    public function __construct(string $message, array $fieldErrors = null)
    {
        // sanitize
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $sanitizedFieldErrors = null;
        if ($fieldErrors !== null) {
            $sanitizedFieldErrors = [];
            foreach ($fieldErrors as $field => $error) {
                if (is_string($field) && is_string($error)) {
                    $sanitizedFieldErrors[$field] = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
                }
            }
        }

        parent::__construct(400, $message, $sanitizedFieldErrors);
    }
}
