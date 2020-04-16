<?php

namespace Kistate\OOS\Model;


class InitiateMultipartUploadInfo
{
    public function __construct()
    {
    }
    private $bucket;
    private $key;
    private $uploadId;

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
    public function getUploadId(){
        return $this->uploadId;
    }

    public function setUploadId($uploadId){
        $this->uploadId = $uploadId;
    }
}

