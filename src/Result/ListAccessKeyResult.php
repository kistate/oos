<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Model\KeyInfo;
use Kistate\OOS\Model\ListKeyInfo;

/**
 * Class ListBucketsResult
 *
 * @package OOS\Result
 */
class ListAccessKeyResult extends Result
{
    /**
     * @return ListKeyInfo
     */
    protected function parseDataFromResponse()
    {
        $keyList = array();
        $IsTruncated = "";
        $Marker = "";
        $userName = "";
        $content = $this->rawResponse->body;
        $xml = new \SimpleXMLElement($content);
        if (isset($xml->ListAccessKeysResult) && isset($xml->ListAccessKeysResult->IsTruncated))
        {
            $IsTruncated = strval($xml->ListAccessKeysResult->IsTruncated);
        }
        if (isset($xml->ListAccessKeysResult) && isset($xml->ListAccessKeysResult->Marker))
        {
            $Marker = strval($xml->ListAccessKeysResult->Marker);
        }
        if (isset($xml->ListAccessKeysResult) && isset($xml->ListAccessKeysResult->UserName))
        {
            $userName = strval($xml->ListAccessKeysResult->UserName);
        }

        if (isset($xml->ListAccessKeysResult) && isset($xml->ListAccessKeysResult->AccessKeyMetadata)
        && isset($xml->ListAccessKeysResult->AccessKeyMetadata->member)) {
            foreach ($xml->ListAccessKeysResult->AccessKeyMetadata->member as $keyOne) {
                $keyInfo = new KeyInfo(
                    strval($keyOne->UserName),
                    strval($keyOne->AccessKeyId),
                    strval($keyOne->Status),
                    "",
                    strval($keyOne->IsPrimary));
                $keyList[] = $keyInfo;
            }
        }
        $listKeyInfo = new ListKeyInfo($keyList,$IsTruncated,$Marker);
        $listKeyInfo->setUserName($userName);
        return $listKeyInfo;
    }
}