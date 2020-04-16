<?php

namespace Kistate\OOS\Result;


/**
 * Class HeaderResult
 * @package OOS\Result
 */
class HeaderResult extends Result
{
    /**
     * The returned ResponseCore header is used as the return data
     *
     * @return array
     */
    protected function parseDataFromResponse()
    {
        return empty($this->rawResponse->header) ? array() : $this->rawResponse->header;
    }

}