<?php

namespace Kistate\OOS\Result;


use Kistate\OOS\Model\LifecycleConfig;

/**
 * Class GetLifecycleResult
 * @package OOS\Result
 */
class GetLifecycleResult extends Result
{
    /**
     *  Parse the LifecycleConfig object from the response
     *
     * @return LifecycleConfig
     * @throws
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new LifecycleConfig();
        $config->parseFromXml($content);
        return $config;
    }

}
