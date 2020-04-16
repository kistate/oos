<?php

namespace Kistate\OOS\Model;

/**
 * Class ListPartsInfo
 * @package OOS\Model
 * @link http://help.ctyun.com/document_detail/oss/api-reference/multipart-upload/ListParts.html
 */
class ListPartsInfo
{

    /**
     * ListPartsInfo constructor.
     * @param string $bucket
     * @param string $key
     * @param string $uploadId
     * @param int $nextPartNumberMarker
     * @param int $maxParts
     * @param string $isTruncated
     * @param string $storageClass
     * @param string $partNumberMarker
     * @param array $listPart
     */
    public function __construct($bucket, $key, $uploadId, $nextPartNumberMarker,
                                $maxParts, $isTruncated,$storageClass,$partNumberMarker,array $listPart)
    {
        $this->bucket = $bucket;
        $this->key = $key;
        $this->uploadId = $uploadId;
        $this->nextPartNumberMarker = $nextPartNumberMarker;
        $this->maxParts = $maxParts;
        $this->isTruncated = $isTruncated;
        $this->listPart = $listPart;
        $this->storageClass = $storageClass;
        $this->partNumberMarker = $partNumberMarker;
    }

    /**
     * @param  Initiator
     */
    public function setInitiator($initiator)
    {
        $this->initiator = $initiator;
    }
    /**
     * @return  Initiator
     */
    public function getInitiator()
    {
        return $this->initiator;
    }

    /**
     * @param  Owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }
    /**
     * @return  Owner
     */
    public function getOwner()
    {
        return $this->owner;
    }
    /**
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getUploadId()
    {
        return $this->uploadId;
    }

    /**
     * @return int
     */
    public function getNextPartNumberMarker()
    {
        return $this->nextPartNumberMarker;
    }

    /**
     * @return int
     */
    public function getMaxParts()
    {
        return $this->maxParts;
    }

    /**
     * @return string
     */
    public function getIsTruncated()
    {
        return $this->isTruncated;
    }

    /**
     * @return string
     */
    public function getStorageClass()
    {
        return $this->storageClass;
    }
    /**
     * @return string
     */
    public function getPartNumberMarker()
    {
        return $this->partNumberMarker;
    }
    /**
     * @return PartInfo[]
     */
    public function getListPart()
    {
        return $this->listPart;
    }

    private $bucket = "";
    private $key = "";
    private $uploadId = "";
    private $nextPartNumberMarker = 0;
    private $maxParts = 0;
    private $isTruncated = "";
    private $storageClass = "";
    private $partNumberMarker = "";
    private $listPart = array();

    private $initiator;
    private $owner;
}