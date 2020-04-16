<?php

namespace Kistate\OOS\Result;


use Kistate\OOS\Model\AccelerateConfig;

/**
 * Class GetAccelerateResult
 * @package OOS\Result
 */
class GetAccelerateResult extends Result
{
    /**
     * Parse AccelerateConfig data
     *
     * @return AccelerateConfig
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new AccelerateConfig();
        $config->parseFromXml($content);
        return $config;
    }

}