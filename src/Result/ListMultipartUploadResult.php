<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Core\OosUtil;
use Kistate\OOS\Model\ListMultipartUploadInfo;
use Kistate\OOS\Model\UploadInfo;
use Kistate\OOS\Model\Owner;
use Kistate\OOS\Model\Initiator;

/**
 * Class ListMultipartUploadResult
 * @package OOS\Result
 */
class ListMultipartUploadResult extends Result
{
    /**
     * Parse the return data from the ListMultipartUpload interface
     *
     * @return ListMultipartUploadInfo
     * @throws
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $xml = simplexml_load_string($content);

        $encodingType = isset($xml->EncodingType) ? strval($xml->EncodingType) : "";
        $bucket = isset($xml->Bucket) ? strval($xml->Bucket) : "";
        $keyMarker = isset($xml->KeyMarker) ? strval($xml->KeyMarker) : "";
        $keyMarker = OosUtil::decodeKey($keyMarker, $encodingType);
        $uploadIdMarker = isset($xml->UploadIdMarker) ? strval($xml->UploadIdMarker) : "";
        $nextKeyMarker = isset($xml->NextKeyMarker) ? strval($xml->NextKeyMarker) : "";
        $nextKeyMarker = OosUtil::decodeKey($nextKeyMarker, $encodingType);
        $nextUploadIdMarker = isset($xml->NextUploadIdMarker) ? strval($xml->NextUploadIdMarker) : "";
        $delimiter = isset($xml->Delimiter) ? strval($xml->Delimiter) : "";
        $delimiter = OosUtil::decodeKey($delimiter, $encodingType);
        $prefix = isset($xml->Prefix) ? strval($xml->Prefix) : "";
        $prefix = OosUtil::decodeKey($prefix, $encodingType);
        $maxUploads = isset($xml->MaxUploads) ? intval($xml->MaxUploads) : 0;
        $isTruncated = isset($xml->IsTruncated) ? strval($xml->IsTruncated) : "";
        $commonPrefixes_prefix = isset($xml->CommonPrefixes->Prefix) ? strval($xml->CommonPrefixes->Prefix) : "";

        $listUpload = array();

        if (isset($xml->Upload)) {
            foreach ($xml->Upload as $upload) {
                $key = isset($upload->Key) ? strval($upload->Key) : "";
                $key = OosUtil::decodeKey($key, $encodingType);
                $uploadId = isset($upload->UploadId) ? strval($upload->UploadId) : "";
                $storageClass = isset($upload->StorageClass) ? strval($upload->StorageClass) : "";
                $initiated = isset($upload->Initiated) ? strval($upload->Initiated) : "";

                $uploadInfo = new UploadInfo($key, $uploadId, $initiated);
                $uploadInfo->setStorageClass($storageClass);

                //Initiator

                $initiator_id = isset($upload->Initiator->ID) ? strval($upload->Initiator->ID) : "";
                $initiator_displayName = isset($upload->Initiator->DisplayName) ? strval($upload->Initiator->DisplayName) : "";
                $initiator = new Initiator($initiator_id,$initiator_displayName);

                $uploadInfo->setInitiator($initiator);

                //Owner
                $owner_id = isset($upload->Owner->ID) ? strval($upload->Owner->ID) : "";
                $owner_displayName = isset($upload->Owner->DisplayName) ? strval($upload->Owner->DisplayName) : "";
                $owner = new Owner($owner_id,$owner_displayName);

                $uploadInfo->setOwner($owner);

                $listUpload[] = $uploadInfo;
            }
        }

        return new ListMultipartUploadInfo($bucket, $keyMarker, $uploadIdMarker,
            $nextKeyMarker, $nextUploadIdMarker,
            $delimiter, $prefix, $maxUploads, $isTruncated, $commonPrefixes_prefix,$listUpload);
    }
}