<?php

namespace Kistate\OOS\Model;

use Kistate\OOS\Model\KeyInfo;
/**
 * Class ListKeyInfo
 *
 * It's the type of return value of ListKeyInfo.
 *
 * @package OOS\Model
 */
class ListKeyInfo
{
    /**
     * BucketListInfo constructor.
     * @param array $KeyList
     * @param string $IsTruncated
     * @param string $Marker
     */
    public function __construct(array $KeyList,$IsTruncated,$Marker)
    {
        $this->keyList = $KeyList;
        $this->isTruncated = $IsTruncated;
        $this->marker = $Marker;
    }
    /**
     * Get the IsTruncated
     *
     * @return string
     */
    public function getIsTruncated()
    {
        return $this->isTruncated;
    }

    /**
     * Get the Marker
     *
     * @return string
     */
    public function getMarker()
    {
        return $this->marker;
    }

    /**
     * Get the BucketInfo list
     *
     * @return KeyInfo[]
     */
    public function getKeyList()
    {
        return $this->keyList;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    private $keyList = array();
    private $isTruncated = "";
    private $marker = "";

    private $userName = "";

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string
     */
    public function setUserName($userName)
    {
        $this->userName =$userName;
    }
}