<?php

namespace Kistate\OOS\Result;


class GetPolicyResult extends Result
{
    /**
     * @return string
     */
    public function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        return $content;
    }


}