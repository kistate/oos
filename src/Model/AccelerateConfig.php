<?php

namespace Kistate\OOS\Model;
use Kistate\OOS\Core\OosException;

/**
 * Class AccelerateConfig
 *
 * @package OOS\Model
 */
class AccelerateConfig implements XmlConfig
{
    /**
     * @param string $strXml
     * @return null
     */
    public function parseFromXml($strXml)
    {
        $xml = simplexml_load_string($strXml);
        $this->Status = isset($xml->Status)? strval($xml->Status) : "";

        if(isset($xml->IPWhiteLists)){
            foreach ($xml->IPWhiteLists->IP as $key => $IP) {
                $this->IPWhiteLists[] = strval($IP);
            }
        }
    }


    /**
     * serialize the AccelerateConfig object into xml string
     * @throws
     * @return string
     */
    public function serializeToXml()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><AccelerateConfiguration></AccelerateConfiguration>');

        //必须
        if (!isset($this->Status)) {
            throw new OosException("Status is null");
        }

        $xml->addChild('Status', $this->Status);

        if(isset($this->IPWhiteLists)){
            $refererList = $xml->addChild('IPWhiteLists');
            foreach ($this->IPWhiteLists as $IP) {
                $refererList->addChild('IP', $IP);
            }
        }

        return $xml->asXML();
    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->serializeToXml();
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * @param string Status
     */
    public function setStatus($Status)
    {
        //Enabled|Suspended
        if(!($Status == "Enabled" || $Status == "Suspended")){
            throw new OosException("Invalid status:" . $Status);
        }
        $this->Status = $Status;
    }

    /**
     * @return array
     */
    public function getIPWhiteLists()
    {
        return $this->IPWhiteLists;
    }
    /**
     * @param array $IPWhiteLists
     */
    public function addIPWhiteLists($IPWhiteLists)
    {
        $this->IPWhiteLists[] = $IPWhiteLists;
    }
    private $Status;
    private $IPWhiteLists;
}