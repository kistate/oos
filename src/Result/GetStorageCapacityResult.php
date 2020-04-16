<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Core\OosException;

/**
 * Class AclResult  GetBucketAcl interface returns the result class, encapsulated
 * The returned xml data is parsed
 *
 * @package OOS\Result
 */
class GetStorageCapacityResult extends Result
{
    /**
     * Parse data from response
     * 
     * @return string
     * @throws OosException
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        if (empty($content)) {
            throw new OosException("body is null");
        }
        $xml = simplexml_load_string($content);
        if (isset($xml->StorageCapacity)) {
            return intval($xml->StorageCapacity);
        } else {
            throw new OosException("xml format exception");
        }
    }
}