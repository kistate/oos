<?php

namespace Kistate\OOS\Model;


use Kistate\OOS\Core\OosException;


/**
 * Class WebsiteConfig
 * @package OOS\Model
 * @link http://help.ctyun.com/document_detail/oss/api-reference/bucket/PutBucketWebsite.html
 */
class WebsiteConfig implements XmlConfig
{
    /**
     * WebsiteConfig constructor.
     * @param  string $indexDocument
     * @param  string $errorDocument
     */
    public function __construct()
    {

    }

    public function setIndexDocument($indexDocument){
        $this->indexDocument = $indexDocument;
    }
    /**
     * @return string
     */
    public function getIndexDocument()
    {
        return $this->indexDocument;
    }

    public function setErrorDocument($errorDocument){
        $this->errorDocument = $errorDocument;
    }
    /**
     * @return string
     */
    public function getErrorDocument()
    {
        return $this->errorDocument;
    }
    /**
     * @param string $strXml
     * @return null
     */
    public function parseFromXml($strXml)
    {
        $xml = simplexml_load_string($strXml);
        if (isset($xml->IndexDocument) && isset($xml->IndexDocument->Suffix)) {
            $this->indexDocument = strval($xml->IndexDocument->Suffix);
        }
        if (isset($xml->ErrorDocument) && isset($xml->ErrorDocument->Key)) {
            $this->errorDocument = strval($xml->ErrorDocument->Key);
        }
    }

    /**
     * Serialize the WebsiteConfig object into xml string.
     *
     * @return string
     * @throws OosException
     */
    public function serializeToXml()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><WebsiteConfiguration></WebsiteConfiguration>');
        if(isset($this->indexDocument)){
            $index_document_part = $xml->addChild('IndexDocument');
            $index_document_part->addChild('Suffix', $this->indexDocument);
        }
        else{
            throw new OosException("Index document is null.");
        }

        if(isset($this->errorDocument)) {
            $error_document_part = $xml->addChild('ErrorDocument');
            $error_document_part->addChild('Key', $this->errorDocument);
        }
        return $xml->asXML();
    }

    /**
     * @return string
     */
    private $indexDocument;
    private $errorDocument;
}