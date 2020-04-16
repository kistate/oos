<?php

namespace Kistate\OOS\Result;


/**
 * Class BodyResult
 * @package OOS\Result
 */
class BodyResult extends Result
{
    /**
     * @return string
     */
    protected function parseDataFromResponse()
    {
        return empty($this->rawResponse->body) ? "" : $this->rawResponse->body;
    }
}