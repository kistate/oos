<?php

namespace Kistate\OOS\Model;

/**
 *
 * Class CopyPartInfo
 *
 * @package OOS\Model
 */
class CopyPartInfo
{
    /**
     * GetObjectInfo constructor.
     *
     */
    public function __construct()
    {
      
    }

    /**
     * @return string
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    public function setLastModified($lastModified)
    {
        return $this->lastModified = $lastModified;
    }

    /**
     * @return string
     */
    public function getETag()
    {
        return $this->eTag;
    }
    public function setETag($eTag)
    {
        return $this->eTag = $eTag;
    }

    private $lastModified = "";
    private $eTag = "";

}