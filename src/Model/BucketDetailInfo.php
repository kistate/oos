<?php

namespace Kistate\OOS\Model;

use Kistate\OOS\Config\DomainConfig;
use Kistate\OOS\Core\OosException;
use Kistate\OOS\Config\Config;

class MetadataLocationConstraint
{
    public function __construct($location)
    {
        $this->location = $location;
    }
    private $location;

    public function getLocation(){
        return $this->location;
    }
}


class BucketDetailInfo
{

    public function __construct()
    {
    }

    /**
     * @param MetadataLocationConstraint $metadataLocationConstraint
     * @throws OosException
     */
    public function setMetaLocation($metadataLocationConstraint)
    {
        //版本5的endpoint 无法使用本接口
        if(DomainConfig::isS5Endpoint(Config::OOS_ENDPOINT)){
            throw new OosException('The metadata location of the bucket. This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.');
        }

        $metaLocation = $metadataLocationConstraint->getLocation();
        if(!isset($metaLocation) || $metaLocation == null){
           // throw new OosException(' MetadataRegion is not specified.');
        }

        else{
            /*
            if(!DomainConfig::isValidRegion($metaLocation)){
                throw new OosException($metaLocation . '  is invalid region.');
            }
            */
            $this->metadataLocationConstraint = $metadataLocationConstraint;
        }
    }

    /**
     * @return MetadataLocationConstraint
     */
    public function getMetaLocation(){

        $metadataLocationConstraint = $this->metadataLocationConstraint;
        if(!isset($metadataLocationConstraint))
            return new MetadataLocationConstraint("");

        return $this->metadataLocationConstraint;
    }

    public function setDataLocation($dataLocationConstraint)
    {
        //版本5的endpoint 无法使用本接口
        if(DomainConfig::isS5Endpoint(Config::OOS_ENDPOINT)){
            throw new OosException('The metadata location of the bucket. This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.');
        }

        if(isset($dataLocationConstraint) && $dataLocationConstraint != null){
            //Type
            $type = $dataLocationConstraint->getType();

            if(!isset($type) || $type == null){
                Print( ' Data Location\'s type is not specified.\n');
                $type = "Local";
            }
            else{
                if(strtolower($type) == strtolower("Local") || strtolower($type) == strtolower("Specified")){
                }
                else{
                    throw new OosException('Type:' . $type . ' is invalid.');
                }
            }

            //ScheduleStrategy
            $scheduleStrategy = $dataLocationConstraint->getScheduleStrategy();

            if(!isset($scheduleStrategy) || $scheduleStrategy == null){
                Print( ' Data Location\'s scheduleStrategy is not specified.\n');
                $scheduleStrategy = "Allowed";
            }
            else{
                if(strtolower($scheduleStrategy) == strtolower("Allowed") || strtolower($scheduleStrategy) == strtolower("NotAllowed")){

                }
                else{
                    throw new OosException( 'ScheduleStrategy:' . $scheduleStrategy . ' is invalid.');
                }
            }

            //locationList
            $locationList = $dataLocationConstraint->getLocationList();
            if(!isset($locationList) || $locationList == null || sizeof($locationList)<1) {

            }
            else{
                /*
                foreach ($locationList as $location ){
                    if(!DomainConfig::isValidRegion($location)){
                        throw new OosException( 'Region:' . $location . '  is invalid region.');
                    }
                }
                */
            }

            $dataLocation = new DataLocation($type,$locationList,$scheduleStrategy);
            $this->dataLocationConstraint = $dataLocation;
        }
    }

    /**
     * @return DataLocation
     */
    public function getDataLocation(){

        $dataLocationConstraint = $this->dataLocationConstraint;
        if(!isset($dataLocationConstraint)){
            $dataLocationConstraint = new DataLocation("Local",array(),"");
            return $dataLocationConstraint;
        }

        return $this->dataLocationConstraint;
    }

    public function serializeToXml()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><CreateBucketConfiguration ></CreateBucketConfiguration >');
        if(isset($this->metadataLocationConstraint)){
            $metadataLocationConstraintXml = $xml->addChild('MetadataLocationConstraint');
            $metadataLocationConstraintXml->addChild('Location', $this->metadataLocationConstraint->getLocation());
        }

        if(isset($this->dataLocationConstraint)){
            $dataLocationConstraintXml = $xml->addChild('DataLocationConstraint');
            $type = $this->dataLocationConstraint->getType();
            if(isset($type)){
                $dataLocationConstraintXml->addChild('Type', $this->dataLocationConstraint->getType());
            }

            $locationList = $this->dataLocationConstraint->getLocationList();
            if(isset($locationList)){
                $dataLocationConstraintLocationListXml = $dataLocationConstraintXml->addChild('LocationList');
                foreach ($locationList as $location ){
                    $dataLocationConstraintLocationListXml->addChild('Location', $location);
                }
            }

            $scheduleStrategy = $this->dataLocationConstraint->getScheduleStrategy();
            if(isset($scheduleStrategy)){
                if(isset($scheduleStrategy) && $scheduleStrategy != null) {
                    $dataLocationConstraintXml->addChild('ScheduleStrategy', $scheduleStrategy);
                }
            }
        }
        return $xml->asXML();
    }

    private $metadataLocationConstraint;
    private $dataLocationConstraint;
}

