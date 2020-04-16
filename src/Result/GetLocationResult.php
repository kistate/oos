<?php
namespace Kistate\OOS\Result;

use Kistate\OOS\Core\OosException;
use Kistate\OOS\Model\BucketDetailInfo;
use Kistate\OOS\Model\MetadataLocationConstraint;
use Kistate\OOS\Model\DataLocation;
/**
 * Class GetLocationResult getBucketLocation interface returns the result class, encapsulated
 * The returned xml data is parsed
 *
 * @package OOS\Result
 */
class GetLocationResult extends Result
{

    /**
     * Parse data from response
     * 
     * @return BucketDetailInfo
     * @throws
     */
    public function parseDataFromResponse()
    {
        $strXml = $this->rawResponse->body;
        if (empty($strXml)) {
            throw new OosException("body is null");
        }

        $bucketDetailInfo= new BucketDetailInfo();
        $xml = simplexml_load_string($strXml);
        $metaLocation = $xml->MetadataLocationConstraint->Location;
        $metadataLocationConstraint = new MetadataLocationConstraint($metaLocation);
        $bucketDetailInfo->setMetaLocation($metadataLocationConstraint);

        $type = "Local";
        if(isset($xml->DataLocationConstraint->Type))
            $type = $xml->DataLocationConstraint->Type;

        $locationList = array();
        if(isset($xml->DataLocationConstraint->LocationList)){
            foreach ($xml->DataLocationConstraint->LocationList->Location as $key => $datLocation) {
                $locationList[] = strval($datLocation);
            }
        }

        $scheduleStrategy = "Allowed";
        if(isset($xml->DataLocationConstraint->ScheduleStrategy))
            $scheduleStrategy = $xml->DataLocationConstraint->ScheduleStrategy;

        $dataLocationConstraint = new DataLocation($type,$locationList,$scheduleStrategy);

        $bucketDetailInfo->setDataLocation($dataLocationConstraint);

        return $bucketDetailInfo;
    }
}