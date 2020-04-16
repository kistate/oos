<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Model\ListPartsInfo;
use Kistate\OOS\Model\PartInfo;
use Kistate\OOS\Model\Owner;
use Kistate\OOS\Model\Initiator;


/**
 * Class ListPartsResult
 * @package OOS\Result
 */
class ListPartsResult extends Result
{
    /**
     * Parse the xml data returned by the ListParts interface
     *
     * @return ListPartsInfo
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $xml = simplexml_load_string($content);

        $bucket = isset($xml->Bucket) ? strval($xml->Bucket) : "";
        $key = isset($xml->Key) ? strval($xml->Key) : "";
        $uploadId = isset($xml->UploadId) ? strval($xml->UploadId) : "";

        $nextPartNumberMarker = isset($xml->NextPartNumberMarker) ? intval($xml->NextPartNumberMarker) : "";
        $maxParts = isset($xml->MaxParts) ? intval($xml->MaxParts) : "";
        $isTruncated = isset($xml->IsTruncated) ? strval($xml->IsTruncated) : "";

        $storageClass = isset($xml->StorageClass) ? strval($xml->StorageClass) : "";
        $partNumberMarker = isset($xml->PartNumberMarker) ? strval($xml->PartNumberMarker) : "";

        $partList = array();
        if (isset($xml->Part)) {
            foreach ($xml->Part as $part) {
                $partNumber = isset($part->PartNumber) ? intval($part->PartNumber) : "";
                $lastModified = isset($part->LastModified) ? strval($part->LastModified) : "";
                $eTag = isset($part->ETag) ? strval($part->ETag) : "";
                $size = isset($part->Size) ? intval($part->Size) : "";

                $partInfo = new PartInfo($partNumber, $lastModified, $eTag, $size);

                $partList[] = $partInfo;
            }
        }

        $listPartsInfo = new ListPartsInfo($bucket, $key, $uploadId, $nextPartNumberMarker,
            $maxParts, $isTruncated,$storageClass, $partNumberMarker,$partList);
        //Initiator
        $initiator = $xml->Initiator;
        $initiator_id = isset($initiator) && isset($initiator->ID) ? strval($initiator->ID) : "";
        $initiator_displayName = isset($initiator) && isset($initiator->DisplayName) ? strval($initiator->DisplayName) : "";
        $initiator = new Initiator($initiator_id,$initiator_displayName);

        $listPartsInfo->setInitiator($initiator);

        //Owner
        $owner = $xml->Owner;
        $owner_id = isset($owner) && isset($owner->ID) ? strval($owner->ID) : "";
        $owner_displayName = isset($owner) && isset($owner->DisplayName) ? strval($owner->DisplayName) : "";
        $owner = new Owner($owner_id,$owner_displayName);
        $listPartsInfo->setInitiator($initiator);

        return $listPartsInfo;
    }
}