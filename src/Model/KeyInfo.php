<?php

namespace Kistate\OOS\Model;

/**
 * Class KeyInfo
 * @package OOS\Model
 */
class KeyInfo
{
    /**
     * KeyInfo constructor.
     *
     * @param string $UserName
     * @param string $AccessKeyId
     * @param string $Status
     * @param string $SecretAccessKey
     * @param string $IsPrimary
     */
    public function __construct($UserName, $AccessKeyId, $Status, $SecretAccessKey,$IsPrimary)
    {
        $this->userName = $UserName;
        $this->accessKeyId = $AccessKeyId;
        $this->status = $Status;
        $this->secretAccessKey = $SecretAccessKey;
        $this->isPrimary = $IsPrimary;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return string
     */
    public function getAccessKeyId()
    {
        return $this->accessKeyId;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getSecretAccessKey()
    {
        return $this->secretAccessKey;
    }

    /**
     * @return string
     */
    public function getIsPrimary()
    {
        return $this->isPrimary;
    }

    private $userName = "";
    private $accessKeyId = "";
    private $status = "";
    private $secretAccessKey = "";
    private $isPrimary = "";
}