<?php

namespace Kistate\OOS\Result;

use Kistate\OOS\Model\CnameConfig;

class GetCnameResult extends Result
{
    /**
     * @return CnameConfig
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new CnameConfig();
        $config->parseFromXml($content);
        return $config;
    }
}