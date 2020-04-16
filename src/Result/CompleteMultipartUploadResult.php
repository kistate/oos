<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Model\CompleteMultipartUploadInfo;
/**
 * Class CompleteMultipartUploadResult
 * @package OOS\Result
 * @return CompleteMultipartUploadInfo
 */
class CompleteMultipartUploadResult extends Result
{
    /**
     * @return CompleteMultipartUploadInfo
     */
    protected function parseDataFromResponse()
    {
        $strXml = $this->rawResponse->body;
        if (empty($strXml)) {
            throw new OosException("body is null");
        }

        $xml = simplexml_load_string($strXml);
        $completeInfo = new CompleteMultipartUploadInfo();

        if (isset($xml->Bucket)) {
            $completeInfo->setBucket($xml->Bucket);
        }
        if (isset($xml->Key)) {
            $completeInfo->setKey($xml->Key);
        }
        if (isset($xml->Location)) {
            $completeInfo->setLocation($xml->Location);
        }
        if (isset($xml->Etag)) {
            $completeInfo->setEtag($xml->Etag);
        }
        return $completeInfo;
    }
}
