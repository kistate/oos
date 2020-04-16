<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Core\OosUtil;
use Kistate\OOS\Model\ObjectInfo;
use Kistate\OOS\Model\ObjectListInfo;
use Kistate\OOS\Model\Owner;
use Kistate\OOS\Model\PrefixInfo;
use SimpleXMLElement;

/**
 * Class ListObjectsResult
 * @package OOS\Result
 */
class ListObjectsResult extends Result
{
    /**
     * Parse the xml data returned by the ListObjects interface
     *
     * return ObjectListInfo
     */
    protected function parseDataFromResponse()
    {
        $xml = new SimpleXMLElement($this->rawResponse->body);

        $encodingType = isset($xml->EncodingType) ? strval($xml->EncodingType) : "";

        $objectList = $this->parseObjectList($xml, $encodingType);
        $prefixList = $this->parsePrefixList($xml, $encodingType);

        $bucketName = isset($xml->Name) ? strval($xml->Name) : "";
        $prefix = isset($xml->Prefix) ? strval($xml->Prefix) : "";
        $prefix = OosUtil::decodeKey($prefix, $encodingType);

        $marker = isset($xml->Marker) ? strval($xml->Marker) : "";
        $marker = OosUtil::decodeKey($marker, $encodingType);

        $maxKeys = isset($xml->MaxKeys) ? intval($xml->MaxKeys) : 0;

        $delimiter = isset($xml->Delimiter) ? strval($xml->Delimiter) : "";
        $delimiter = OosUtil::decodeKey($delimiter, $encodingType);

        $isTruncated = isset($xml->IsTruncated) ? strval($xml->IsTruncated) : "";

        $nextMarker = isset($xml->NextMarker) ? strval($xml->NextMarker) : "";
        $nextMarker = OosUtil::decodeKey($nextMarker, $encodingType);

        return new ObjectListInfo($bucketName, $prefix, $marker, $nextMarker,
            $maxKeys, $delimiter, $isTruncated, $objectList, $prefixList);
    }

    private function parseObjectList($xml, $encodingType)
    {
        $retList = array();
        if (isset($xml->Contents)) {
            foreach ($xml->Contents as $content) {
                $key = isset($content->Key) ? strval($content->Key) : "";
                $key = OosUtil::decodeKey($key, $encodingType);

                $lastModified = isset($content->LastModified) ? strval($content->LastModified) : "";

                $eTag = isset($content->ETag) ? strval($content->ETag) : "";

                $type = isset($content->Type) ? strval($content->Type) : "";

                $size = isset($content->Size) ? intval($content->Size) : 0;

                $storageClass = isset($content->StorageClass) ? strval($content->StorageClass) : "";

                $id = isset($content->Owner->ID) ? strval($content->Owner->ID) : "";

                $displayName = isset($content->Owner->DisplayName) ? strval($content->Owner->DisplayName) : "";

                $objectInfo = new ObjectInfo($key, $lastModified, $eTag, $type, $size, $storageClass);
                $objectInfo->setOwner(new Owner($id,$displayName));

                $retList[] = $objectInfo;
            }
        }
        return $retList;
    }

    private function parsePrefixList($xml, $encodingType)
    {
        $retList = array();
        if (isset($xml->CommonPrefixes)) {
            foreach ($xml->CommonPrefixes as $commonPrefix) {
                $prefix = isset($commonPrefix->Prefix) ? strval($commonPrefix->Prefix) : "";
                $prefix = OosUtil::decodeKey($prefix, $encodingType);
                $retList[] = new PrefixInfo($prefix);
            }
        }
        return $retList;
    }
}