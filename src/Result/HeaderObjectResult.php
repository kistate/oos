<?php

namespace Kistate\OOS\Result;
use Kistate\OOS\Model\ObjectInfo;


/**
 * Class HeaderObjectResult
 * @package OOS\Result
 */
class HeaderObjectResult extends Result
{
    /**
     * The returned ResponseCore header is used as the return data
     *
     * @return ObjectInfo
     */
    protected function parseDataFromResponse()
    {
        $headers =  empty($this->rawResponse->header) ? array() : $this->rawResponse->header;
        $key = "";
        $lastModified = isset($headers["last-modified"]) ? $headers["last-modified"] : "";
        $eTag =  isset($headers["etag"]) ? $headers["etag"] : "";
        $type = isset($headers["content-type"]) ? $headers["content-type"] : "";
        $size = isset($headers["content-length"]) ? $headers["content-length"] : "";
        $storageClass = "";
        $amzExpiration = isset($headers["x-amz-expiration"]) ? $headers["x-amz-expiration"] : "";

        $objectInfo = new ObjectInfo($key, $lastModified, $eTag, $type, $size, $storageClass);
        $objectInfo->setAmzexpiration($amzExpiration);
        return $objectInfo;
    }

}