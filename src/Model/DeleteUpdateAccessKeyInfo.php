<?php

namespace Kistate\OOS\Model;


/**
 * Bucket information class. This is the type of element in BucketListInfo's
 *
 * Class BucketInfo
 * @package OOS\Model
 */
class DeleteUpdateAccessKeyInfo
{
    /**
     * DeleteUpdateAccessKeyInfo constructor.
     *
     */
    public function __construct()
    {

    }
    /**
     * RequestId
     *
     * @var string
     */
    private $requestId;

    public function getRequestId(){
        return $this->requestId;
    }
    public function setRequestId($requestId){
        $this->requestId = $requestId;
    }
}