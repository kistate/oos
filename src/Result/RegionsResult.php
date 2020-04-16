<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Core\OosException;
use Kistate\OOS\Model\BucketRegion;
/**
 * The type of the return value of getRegions, it wraps the data parsed from xml.
 *
 * @package OOS\Result
 */
class RegionsResult extends Result
{
    /**
     * @return BucketRegion
     * @throws OosException
     */
    protected function parseDataFromResponse()
    {
        $strXml = $this->rawResponse->body;
        if (empty($strXml)) {
            throw new OosException("body is null");
        }

        $bucketRegion = new BucketRegion();

        $xml = simplexml_load_string($strXml);

        $metaRegions = array();
        $dataRegions = array();
        foreach ($xml->MetadataRegions->Region as $key => $metaRegion) {
            $metaRegions[] = strval($metaRegion);
        }

        foreach ($xml->DataRegions->Region as $key => $datRegion) {
            $dataRegions[] = strval($datRegion);
        }
        $bucketRegion->setMetaRegions($metaRegions);
        $bucketRegion->setDataRegions($dataRegions);
        return $bucketRegion;
    }
}