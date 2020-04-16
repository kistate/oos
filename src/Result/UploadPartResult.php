<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Core\OosException;

/**
 * Class UploadPartResult
 * @package OOS\Result
 */
class UploadPartResult extends Result
{
    /**
     * 结果中part的ETag
     *
     * @return string
     * @throws OosException
     */
    protected function parseDataFromResponse()
    {
        $header = $this->rawResponse->header;
        if (isset($header["etag"])) {
            return $header["etag"];
        }
        throw new OosException("cannot get ETag");

    }
}