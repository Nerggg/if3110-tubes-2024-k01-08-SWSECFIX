<?php

namespace src\utils;

class Validator
{
    // Error fields
    private $errorFields = [];
    private const MAX_FILES = 10; // 10 just for practicality. change based on what the program needs
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf'
    ];

    /**
     * Validate the data (associative array) against the rules
     */
    public function validate($data, $rules)
    {
        foreach ($rules as $field => $rule) {
            $fieldInMessage = ucfirst(str_replace("-", " ", $field));
            $value = isset($data[$field]) ? $data[$field] : null;

            foreach ($rule as $validation => $param) {
                // If doesn't receive param
                if (is_numeric($validation)) {
                    $validation = $param;
                    $param = null;
                }

                switch ($validation) {
                    case 'required':
                        if (empty($value)) {
                            // echo "in required";
                            // if 0 or false is passed, it is not empty
                            if ($value === 0 || $value === "0" || $value === false) {
                                continue 2;
                            }

                            $message = ucfirst("$fieldInMessage is required");
                            $this->addError($field, $message);
                        }
                        break;
                    case 'optional':
                        if (empty($value)) {
                            continue 2;
                        }
                        break;
                    case 'integer':
                        if (!is_int($value)) {
                            // echo "in integer";
                            $message = ucfirst("$fieldInMessage must be an integer");
                            $this->addError($field, $message);
                        }
                        break;
                    case 'min':
                        if (strlen($value) < $param) {
                            // echo "in min";
                            $message = ucfirst("$fieldInMessage must be at least $param characters");
                            $this->addError($field, $message);
                        }
                        break;
                    case 'max':
                        if (strlen($value) > $param) {
                            // echo "in max";
                            $message = ucfirst("$fieldInMessage must be no more than $param characters");
                            $this->addError($field, $message);
                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            // echo "in email";
                            $message = ucfirst("$fieldInMessage must be a valid email address");
                            $this->addError($field, $message);
                        }
                        break;
                    case 'enum':
                        if (!in_array($value, $param)) {
                            // echo "in enum";
                            $message = ucfirst("$fieldInMessage must be one of " . implode(', ', $param));
                            $this->addError($field, $message);
                        }
                        break;
                    case 'boolean':
                        if (($value !== '0' && $value !== '1')) {
                            // echo "in boolean";
                            $message = ucfirst("$fieldInMessage must be a boolean");
                            $this->addError($field, $message);
                        }
                        break;
                    case 'requiredFile':
                        if ($value['error'] == UPLOAD_ERR_NO_FILE) {
                            $message = ucfirst("$fieldInMessage is required");
                            $this->addError($field, $message);
                        }
                        break;
                    case 'requiredFiles':
                        foreach ($value['error'] as $key => $error) {
                            if ($error == UPLOAD_ERR_NO_FILE) {
                                $message = ucfirst("$fieldInMessage is required");
                                $this->addError($field, $message);
                            }
                        }
                        break;
                    case 'file':
                        // only check if file exists
                        if ($value['error'] == UPLOAD_ERR_NO_FILE) {
                            continue 2;
                        }
                        // validate file MIME using fileinfo
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $value['tmp_name']);
                        finfo_close($finfo);
                        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
                            $message = ucfirst("$fieldInMessage must be one of " . implode(', ', self::ALLOWED_MIME_TYPES));
                            $this->addError($field, $message);
                        }
                        // max size
                        if ($value['size'] > $param['maxSize']) {
                            $message = ucfirst("$fieldInMessage must be no more than " . $param['maxSize'] . " bytes");
                            $this->addError($field, $message);
                        }
                        break;
                    case 'files':
                        // only check if file exists
                        if ($value['error'][0] == UPLOAD_ERR_NO_FILE) {
                            continue 2;
                        }
                        // limit number of files to prevent DoS
                        if (count($value['name']) > self::MAX_FILES) {
                            $message = ucfirst("$fieldInMessage exceeds maximum allowed files (" . self::MAX_FILES . ")");
                            $this->addError($field, $message);
                            break;
                        }
                        // Validate MIME type and size for each file
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        foreach ($value['tmp_name'] as $key => $tmpName) {
                            $mimeType = finfo_file($finfo, $tmpName);
                            if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
                                $message = ucfirst("$fieldInMessage must be one of " . implode(', ', self::ALLOWED_MIME_TYPES));
                                $this->addError($field, $message);
                            }
                            // max size
                            if ($value['size'][$key] > $param['maxSize']) {
                                $sizeMb = floor($param['maxSize'] / (1024 * 1024));
                                $message = ucfirst("$fieldInMessage must be no more than " . $sizeMb . " MB");
                                $this->addError($field, $message);
                            }
                        }
                        finfo_close($finfo);
                        break;
                }
            }
        }

        return empty($this->errorFields);
    }

    private function addError($field, $message)
    {
        $this->errorFields[$field][] = $message;
    }

    public function getErrorFields()
    {
        return $this->errorFields;
    }
}
