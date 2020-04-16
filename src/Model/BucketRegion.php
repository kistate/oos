<?php

namespace Kistate\OOS\Model;

class BucketRegion
{
    public function __construct()
    {
        $this->metaRegions = array();
        $this->dataRegions = array();
    }
    private $metaRegions;
    private $dataRegions;

    /**
     * @return array
     */
    public function getMetaRegions(){
        return $this->metaRegions;
    }

    public function setMetaRegions($metaRegions){
        $this->metaRegions = $metaRegions;
    }

    /**
     * @return array
     */
    public function getDataRegions(){
        return $this->dataRegions;
    }
    public function setDataRegions($dataRegions){
        $this->dataRegions = $dataRegions;
    }
}

