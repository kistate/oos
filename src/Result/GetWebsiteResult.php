<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Model\WebsiteConfig;

/**
 * Class GetWebsiteResult
 * @package OOS\Result
 */
class GetWebsiteResult extends Result
{
    /**
     * Parse WebsiteConfig data
     *
     * @return WebsiteConfig
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new WebsiteConfig();
        $config->parseFromXml($content);
        return $config;
    }


}