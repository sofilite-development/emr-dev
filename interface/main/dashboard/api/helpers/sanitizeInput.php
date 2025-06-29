<?php

/**
 * Sanitizes and validates input data
 * @param mixed $data Input data to sanitize
 * @param string $type Data type (string, int, float, email, date, etc.)
 * @return mixed Sanitized data or false if validation fails
 */
function sanitizeInput($data, $type = 'string')
{
    if ($data === null) {
        return null;
    }

    switch ($type) {
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT);
        case 'email':
            return filter_var(trim($data), FILTER_VALIDATE_EMAIL);
        case 'date':
            $date = date('Y-m-d', strtotime($data));
            return ($date && $date != '1970-01-01') ? $date : false;
        case 'phone':
            $cleaned = preg_replace('/[^0-9+]/', '', $data);
            return (strlen($cleaned) >= 10) ? $cleaned : false;
        case 'bool':
            return filter_var($data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        case 'string':
        default:
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}
?>