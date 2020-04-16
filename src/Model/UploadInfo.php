<?php

namespace Kistate\OOS\Model;
use Kistate\OOS\Model\Owner;
use Kistate\OOS\Model\Initiator;
/**
 * Class UploadInfo
 *
 * The return value of ListMultipartUpload
 *
 * @package OOS\Model
 */
class UploadInfo
{
    /**
     * UploadInfo constructor.
     *
     * @param string $key
     * @param string $uploadId
     * @param string $initiated
     */
    public function __construct($key, $uploadId, $initiated)
    {
        $this->key = $key;
        $this->uploadId = $uploadId;
        $this->initiated = $initiated;
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
     * @return string
     */
    public function getInitiated()
    {
        return $this->initiated;
    }

    private $key = "";
    private $uploadId = "";
    private $initiated = "";
    private $storageClass = "";

    private $initiator;
    private $owner;

    public function setStorageClass($storageClass)
    {
        $this->storageClass = $storageClass;
    }
    /**
     * @return string
     */
    public function getStorageClass()
    {
        return $this->storageClass;
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
}