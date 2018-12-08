<?php

namespace App\Service;

class JsonRequestService
{
    /**
     * Safely get a key from an array.
     * If the key exists, return the key.
     * Else, return FALSE
     */
    public function getArrayKey($key, $array) {
        return array_key_exists($key, $array) ? $array[$key] : FALSE;
    }

    /**
     * Convert JSON from the request body into an associative array.
     * If the JSON exists and is valid (and not empty, unless $allowEmpty is TRUE), return it as an array.
     * Else, return FALSE
     */
    public function getRequestBody($request, $allowEmpty = FALSE) {
        $parameters = [];
        if ($content = $request->getContent()) {
            $parameters = json_decode($content, true);

            if (!$parameters && !$allowEmpty) {
                return FALSE;
            }

            return $parameters;
        } else {
            return FALSE;
        }
    }
}