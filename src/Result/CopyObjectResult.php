<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Core\OosException;
use Kistate\OOS\Model\CopyPartInfo;
/**
 * Class CopyObjectResult
 * @package OOS\Result
 * @return CopyPartInfo
 */
class CopyObjectResult extends Result
{
    /**
     * @return CopyPartInfo
     */
    protected function parseDataFromResponse()
    {
        $strXml = $this->rawResponse->body;
        if (empty($strXml)) {
            throw new OosException("body is null");
        }

        $xml = simplexml_load_string($strXml);
        $partInfo = new CopyPartInfo();

        if (isset($xml->LastModified)) {
            $partInfo->setLastModified($xml->LastModified);
        }
        if (isset($xml->ETag)) {
            $partInfo->setETag($xml->ETag);
        }
        return $partInfo;
    }
}
