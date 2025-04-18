<?php

namespace src\exceptions;

use Exception;

class BaseHttpException extends Exception
{
    // Store http status code in exception code
    // Store error message in exception message
    // Form field errors (key: field, value: error message) e.g. Array<{ field, message }>
    protected array | null $fieldErrors = null;

    public function __construct(int $code, string $message, array $fieldErrors = null)
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

        parent::__construct($message, $code);
        $this->fieldErrors = $sanitizedFieldErrors;
    }
}
