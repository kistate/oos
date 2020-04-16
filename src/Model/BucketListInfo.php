<?php

namespace Kistate\OOS\Model;
use Kistate\OOS\Model\Owner;

/**
 * Class BucketListInfo
 *
 * It's the type of return value of ListBuckets.
 *
 * @package OOS\Model
 */
class BucketListInfo
{
    /**
     * BucketListInfo constructor.
     */
    public function __construct()
    {
    }

    /**
     * set Owner
     * @param Owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * set Owner
     * @return  Owner
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * set the BucketInfo list
     *
     * @param BucketInfo[] $bucketList
     */
    public function setBucketList($bucketList)
    {
        $this->bucketList = $bucketList;
    }
    /**
     * Get the BucketInfo list
     *
     * @return BucketInfo[]
     */
    public function getBucketList()
    {
        return $this->bucketList;
    }

    /**
     * BucketInfo list
     *
     * @var array
     */
    private $bucketList = array();
    /**
     * BucketInfo list
     *
     * @var Owner
     */
    private $owner;
}