<?php

namespace Kistate\OOS\Model;

use Kistate\OOS\Core\OosException;


/**
 * Class CorsRule
 * @package OOS\Model
 * @link http://help.ctyun.com/document_detail/oss/api-reference/cors/PutBucketcors.html
 */
class CorsRule
{
    /**
     * Add an allowedOrigin rule
     *
     * @param string $allowedOrigin
     */
    public function addAllowedOrigin($allowedOrigin)
    {
        if (!empty($allowedOrigin)) {
            $this->allowedOrigins[] = $allowedOrigin;
        }
    }

    /**
     * Add an allowedMethod rule
     *
     * @param string $allowedMethod
     */
    public function addAllowedMethod($allowedMethod)
    {
        if (!empty($allowedMethod)) {
            $this->allowedMethods[] = $allowedMethod;
        }
    }

    /**
     * Add an allowedHeader rule
     *
     * @param string $allowedHeader
     */
    public function addAllowedHeader($allowedHeader)
    {
        if (!empty($allowedHeader)) {
            $this->allowedHeaders[] = $allowedHeader;
        }
    }

    /**
     * Add an exposeHeader rule
     *
     * @param string $exposeHeader
     */
    public function addExposeHeader($exposeHeader)
    {
        if (!empty($exposeHeader)) {
            $this->exposeHeaders[] = $exposeHeader;
        }
    }

    /**
     * @return int
     */
    public function getMaxAgeSeconds()
    {
        return $this->maxAgeSeconds;
    }

    /**
     * @param int $maxAgeSeconds
     */
    public function setMaxAgeSeconds($maxAgeSeconds)
    {
        $this->maxAgeSeconds = $maxAgeSeconds;
    }

    /**
     * Get the AllowedHeaders list
     *
     * @return string[]
     */
    public function getAllowedHeaders()
    {
        return $this->allowedHeaders;
    }

    /**
     * Get the AllowedOrigins list
     *
     * @return string[]
     */
    public function getAllowedOrigins()
    {
        return $this->allowedOrigins;
    }

    /**
     * Get the AllowedMethods list
     *
     * @return string[]
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }

    /**
     * Get the ExposeHeaders list
     *
     * @return string[]
     */
    public function getExposeHeaders()
    {
        return $this->exposeHeaders;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        //最长255个字符
        if(strlen($id)>255){
            throw new OosException("The max length of ID is 255");
        }
        return $this->id = $id;
    }
    /**
     * Serialize all the rules into the xml represented by parameter $xmlRule
     *
     * @param \SimpleXMLElement $xmlRule
     * @throws OosException
     */
    public function appendToXml(&$xmlRule)
    {
        if (isset($this->id)) {
            $xmlRule->addChild(CorsConfig::OOS_CORS_RULE, $this->id);
        }

        //必须
        if (!isset($this->allowedOrigins) || count($this->allowedOrigins) <1) {
            throw new OosException("allowedOrigins is not set in the Rule");
        }

        foreach ($this->allowedOrigins as $allowedOrigin) {
            $xmlRule->addChild(CorsConfig::OOS_CORS_ALLOWED_ORIGIN, $allowedOrigin);
        }

        //允许跨域的HTTP方法。每个CORSRule应至少包含一个源和一个方法。
        if (!isset($this->allowedMethods) || count($this->allowedMethods) <1) {
            throw new OosException("allowedMethods is not set in the Rule");
        }
        foreach ($this->allowedMethods as $allowedMethod) {
            $xmlRule->addChild(CorsConfig::OOS_CORS_ALLOWED_METHOD, $allowedMethod);
        }
        foreach ($this->allowedHeaders as $allowedHeader) {
            $xmlRule->addChild(CorsConfig::OOS_CORS_ALLOWED_HEADER, $allowedHeader);
        }
        foreach ($this->exposeHeaders as $exposeHeader) {
            $xmlRule->addChild(CorsConfig::OOS_CORS_EXPOSE_HEADER, $exposeHeader);
        }
        $xmlRule->addChild(CorsConfig::OOS_CORS_MAX_AGE_SECONDS, strval($this->maxAgeSeconds));

        //XML请求体不能超过64KB
        if(strlen(strval($xmlRule)) > 64*1024*8){
            throw new OosException("The max length of xml body is 64KB");
        }
    }

    private $allowedHeaders = array();
    private $allowedOrigins = array();
    private $allowedMethods = array();
    private $exposeHeaders = array();
    private $maxAgeSeconds = null;
    private $id = "";
}