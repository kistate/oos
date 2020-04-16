<?php

namespace Kistate\OOS\Core;

/**
 * Class OosException
 *
 * This is the class that OSSClient is expected to thrown, which the caller needs to handle properly.
 * It has the OOS specific errors which is useful for troubleshooting.
 *
 * @package OOS\Core
 */
class OosException extends \Exception
{
    private $details = array();

    function __construct($details)
    {
        if (is_array($details)) {
            $message = $details['code'] . ': ' . $details['message']
                     . ' RequestId: ' . $details['request-id'];
            parent::__construct($message);
            $this->details = $details;
        } else {
            $message = $details;
            $this->details['message'] = $details;
            parent::__construct($message);
        }
    }

    public function getHTTPStatus()
    {
        return isset($this->details['status']) ? $this->details['status'] : '';
    }

    public function getRequestId()
    {
        return isset($this->details['request-id']) ? $this->details['request-id'] : '';
    }

    public function getErrorCode()
    {
        return isset($this->details['code']) ? $this->details['code'] : '';
    }

    public function getErrorMessage()
    {
        return isset($this->details['message']) ? $this->details['message'] : '';
    }

    public function getDetails()
    {
        return isset($this->details['body']) ? $this->details['body'] : '';
    }

    public function printException($funcName){
        print($funcName . ": FAILED\n");
        print("Status" . ": " . $this->getHTTPStatus() . "\n");
        print("Request-id" . ": " . $this->getRequestId() . "\n");
        print("Code" . ": " . $this->getErrorCode() . "\n");
        print("Message" . ": " . $this->getErrorMessage() . "\n");
        print("Details" . ": " . $this->getDetails() . "\n\n");
    }
}
