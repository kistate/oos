<?php

namespace Kistate\OOS\Model;


class CompleteMultipartUploadInfo
{
    public function __construct()
    {
    }
    private $bucket;
    private $key;
    private $location;
    private $etag;

    public function getBucket(){
        return $this->bucket;
    }

    public function setBucket($bucket){
        $this->bucket = $bucket;
    }
    public function getKey(){
        return $this->key;
    }
    public function setKey($key){
        $this->key = $key;
    }
    public function getLocation(){
        return $this->location;
    }

    public function setLocation($location){
        $this->location = $location;
    }

    public function getEtag(){
        return $this->etag;
    }
    public function setEtag($etag){
        $this->etag = $etag;
    }
}

