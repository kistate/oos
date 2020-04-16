<?php
namespace Kistate\OOS;

use Kistate\OOS\Result\ListObjectsResult;
use Kistate\OOS\Core\MimeTypes;
use Kistate\OOS\Core\OosException;
use Kistate\OOS\Http\RequestCore;
use Kistate\OOS\Http\RequestCore_Exception;
use Kistate\OOS\Http\ResponseCore;
use Kistate\OOS\Model\CorsConfig;
use Kistate\OOS\Model\LoggingConfig;
use Kistate\OOS\Result\AclResult;
use Kistate\OOS\Result\GetCorsResult;
use Kistate\OOS\Result\GetPolicyResult;
use Kistate\OOS\Result\GetLifecycleResult;
use Kistate\OOS\Result\GetLocationResult;
use Kistate\OOS\Result\GetLoggingResult;

use Kistate\OOS\Result\GetAccelerateResult;
use Kistate\OOS\Result\GetWebsiteResult;
use Kistate\OOS\Result\HeaderResult;
use Kistate\OOS\Result\InitiateMultipartUploadResult;
use Kistate\OOS\Result\ListBucketsResult;
use Kistate\OOS\Result\ListMultipartUploadResult;
use Kistate\OOS\Model\ListMultipartUploadInfo;
use Kistate\OOS\Result\ListPartsResult;
use Kistate\OOS\Result\PutSetDeleteResult;
use Kistate\OOS\Result\DeleteObjectsResult;
use Kistate\OOS\Result\CopyObjectResult;
use Kistate\OOS\Result\CallbackResult;
use Kistate\OOS\Result\ExistResult;
use Kistate\OOS\Result\AppendResult;
use Kistate\OOS\Result\RegionsResult;
use Kistate\OOS\Result\CreateAccessKeyResult;
use Kistate\OOS\Result\ListAccessKeyResult;
use Kistate\OOS\Result\GetObjectResult;
use Kistate\OOS\Model\ObjectListInfo;
use Kistate\OOS\Result\UploadPartResult;
use Kistate\OOS\Model\BucketListInfo;
use Kistate\OOS\Model\LifecycleConfig;
use Kistate\OOS\Model\RefererConfig;
use Kistate\OOS\Model\WebsiteConfig;
use Kistate\OOS\Core\OosUtil;
use Kistate\OOS\Model\ListPartsInfo;

use Kistate\OOS\Config\DomainConfig;
use Kistate\OOS\Config\Config;

use Kistate\OOS\Model\DataLocation;
use Kistate\OOS\Model\GetObjectInfo;
use Kistate\OOS\Model\BucketDetailInfo;

use Kistate\OOS\Model\KeyInfo;
use Kistate\OOS\Model\ListKeyInfo;

use Kistate\OOS\Model\AccelerateConfig;
use Kistate\OOS\Model\BucketAcl;
use Kistate\OOS\Model\BucketRegion;

use Kistate\OOS\Model\InitiateMultipartUploadInfo;
use Kistate\OOS\Model\CopyPartInfo;

use Kistate\OOS\Result\CompleteMultipartUploadResult;
use Kistate\OOS\Model\CompleteMultipartUploadInfo;

use Kistate\OOS\Result\DeleteUpdateAccessKeyResult;
use Kistate\OOS\Model\DeleteUpdateAccessKeyInfo;
use Kistate\OOS\Model\ObjectInfo;

use Kistate\OOS\Result\HeaderObjectResult;
/**
 * Class OosClient
 *
 * Object Oriented Storage Service(OOS)'s client class, which wraps all OOS APIs user could call to talk to OOS.
 * Users could do operations on bucket, object, including MultipartUpload or setting ACL via an OSSClient instance.
  */
class OosClient
{
    /**
     * Constructor
     *
     * There're a few different ways to create an OosClient object:
     * 1. Most common one from access Id, access Key and the endpoint: $oosClient = new OosClient($id, $key, $endpoint)
     * 2. If the endpoint is the CName (such as www.testoss.com, make sure it's CName binded in the OOS console),
     *    uses $oosClient = new OosClient($id, $key, $endpoint, true)
     * 3. If the endpoint is in IP format, you could use this: $oosClient = new OosClient($id, $key, “1.2.3.4:8900”)
     *
     * @param string $accessKeyId The AccessKeyId from OOS or STS
     * @param string $accessKeySecret The AccessKeySecret from OOS or STS
     * @param string $endpoint The domain name of the datacenter,For example: oos-hz.ctyunapi.cn
     * @param boolean $isCName If this is the CName and binded in the bucket.
     * @param string $securityToken from STS.
     * @param string $requestProxy
     * @throws 
     */
    public function __construct($accessKeyId, $accessKeySecret, $endpoint, $isCName = false,
                                $securityToken = NULL, $requestProxy = NULL)
    {
        $accessKeyId = trim($accessKeyId);
        $accessKeySecret = trim($accessKeySecret);
        $endpoint = trim(trim($endpoint), "/");

        if (empty($accessKeyId)) {
            throw new OosException("access key id is empty");
        }
        if (empty($accessKeySecret)) {
            throw new OosException("access key secret is empty");
        }
        if (empty($endpoint)) {
            throw new OosException("endpoint is empty");
        }

        //判断是否是S5的endpoint
        if(DomainConfig::isS5Endpoint($endpoint)){
            $this->isV5Server = true;
            if(Config::SIGNER_USING_V4){
                throw new OosException($endpoint . ' is a V5 Server，So that can not use V4 Singer.Please check config.');
            }
        }

        $this->hostname = $this->checkEndpoint($endpoint, $isCName);
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->securityToken = $securityToken;
        $this->requestProxy = $requestProxy;
        $this->endpoint = $endpoint;

        self::checkEnv();
    }

    /**
     * Lists the Bucket [GetService]. Not applicable if the endpoint is CName (because CName must be binded to a specific bucket).
     *
     * @param array $options
     * @throws
     * @return BucketListInfo
     */
    public function listBuckets($options = NULL)
    {
        if ($this->hostType === self::OOS_HOST_TYPE_CNAME) {
            throw new OosException("operation is not permitted with CName host");
        }
        $this->precheckOptions($options);
        $options[self::OOS_BUCKET] = '';
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $response = $this->auth($options);
        $result = new ListBucketsResult($response);
        return $result->getData();
    }

    /**
     * @param $bucket
     * @param string $acl
     * @param null $options
     * @param BucketDetailInfo $bucketDetailInfo
     *    metadataLocation	The metadata location of the bucket. This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.
     *    dataLocation	The data location of the bucket. This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.
     *    ScheduleStrategy	The schedule strategy of the bucket. This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.
     * @return null
     * @throws 
     * @throws
     */
    public function createBucket($bucket, $acl = self::OOS_ACL_TYPE_PRIVATE,$options = NULL, $bucketDetailInfo = NULL)
    {
        if(!isset($options) || $options == NULL){
            $options  = array();
        }

        //版本5的endpoint 无需设置$bucketDetailInfo
        if(!$this->isV5Server){
            if(!isset($bucketDetailInfo) || $bucketDetailInfo == NULL){
                throw new OosException( 'The parameter bucketDetailInfo is null.');
            }
        }
        else{
            if(isset($bucketDetailInfo) || $bucketDetailInfo != NULL){
                throw new OosException( 'This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.');
            }
        }

        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;

        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_HEADERS] = array(self::OOS_ACL => $acl);

        //版本6的endpoint 需要设置MetaLocation和DataLocation
        if(!$this->isV5Server){
            $content = $bucketDetailInfo->serializeToXml();
            $options[self::OOS_CONTENT] = $content;
            $options[self::OOS_CONTENT_LENGTH] = strlen($content);
            $options[self::OOS_CONTENT_TYPE] = "application/xml";
        }

        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Deletes bucket
     * The deletion will not succeed if the bucket is not empty (either has objects or parts)
     * To delete a bucket, all its objects and parts must be deleted first.
     *
     * @param string $bucket
     * @param array $options
     * @throws 
     * @return null
     */
    public function deleteBucket($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_DELETE;
        $options[self::OOS_OBJECT] = '/';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Checks if a bucket exists
     *
     * @param string $bucket
     * @return bool
     * @throws
     */
    public function doesBucketExist($bucket)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new ExistResult($response);
        return $result->getData();
    }

    /**
     * head a bucket
     *
     * @param string $bucket
     * @return bool
     * @throws
     */
    public function headBucket($bucket)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_HEAD;
        $options[self::OOS_OBJECT] = '/';

        $response = $this->auth($options);
        $result = new ExistResult($response);
        return $result->getData();
    }

    /**
     * Get the data  location information for the bucket
     * @param string $bucket
     * @param array $options
     * @return BucketDetailInfo
     * @throws
     */
    public function getBucketLocation($bucket, $options = NULL)
    {
        //版本5的endpoint 无法使用本接口
        if($this->isV5Server){
            throw new OosException( 'Get the data regions and metadata regions of the specified bucket. This method is only used for Object Storage Network, the other resource pools can not use this method.');
        }
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'location';
        $response = $this->auth($options);
        $result = new GetLocationResult($response);
        return $result->getData();
    }

    /**
     * Get the Meta information for the Bucket
     *
     * @param string $bucket
     * @param array $options  Refer to the SDK documentation
     * @return array
     * @throws
     */
    public function getBucketMeta($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_HEAD;
        $options[self::OOS_OBJECT] = '/';
        $response = $this->auth($options);
        $result = new HeaderResult($response);
        return $result->getData();
    }

    /**
     * Gets the bucket ACL
     *
     * @param string $bucket
     * @param array $options
     * @throws 
     * @return BucketAcl
     */
    public function getBucketAcl($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new AclResult($response);
        return $result->parseDataFromResponse();
    }

    /**
     * Sets the bucket ACL
     *
     * @param string $bucket bucket name
     * @param string $acl access permissions, valid values are ['private', 'public-read', 'public-read-write']
     * @param array $options by default is empty
     * @throws 
     * @return array()
     */
    public function putBucketAcl($bucket, $acl, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_HEADERS] = array(self::OOS_ACL => $acl);
        $options[self::OOS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Gets object ACL
     *
     * @param string $bucket
     * @param string $object
     * @throws 
     * @return BucketAcl
     */
    public function getObjectAcl($bucket, $object)
    {
        $options = array();
        $this->precheckCommon($bucket, $object, $options, true);
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new AclResult($response);
        return $result->getData();
    }

    /**
     * Sets the object ACL
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $acl access permissions, valid values are ['default', 'private', 'public-read', 'public-read-write']
     * @throws 
     * @return array
     */
    public function putObjectAcl($bucket, $object, $acl)
    {
        $this->precheckCommon($bucket, $object, $options, true);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_HEADERS] = array(self::OOS_OBJECT_ACL => $acl);
        $options[self::OOS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Gets the bucket logging config
     *
     * @param string $bucket bucket name
     * @param array $options by default is empty
     * @throws 
     * @return LoggingConfig
     */
    public function getBucketLogging($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'logging';
        $response = $this->auth($options);
        $result = new GetLoggingResult($response);
        return $result->getData();
    }

    /**
     * Sets the bycket logging config. Only owner can call this API.
     *
     * @param string $bucket bucket name
     * @param string $targetBucket The logging file's bucket
     * @param string $targetPrefix The logging file's prefix
     * @param array $options By default is empty.
     * @throws 
     * @return array()
     */
    public function putBucketLogging($bucket, $targetBucket, $targetPrefix, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $this->precheckBucket($targetBucket, 'targetbucket is not allowed empty');
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'logging';
        $options[self::OOS_CONTENT_TYPE] = 'application/xml';

        $loggingConfig = new LoggingConfig($targetBucket, $targetPrefix);
        $options[self::OOS_CONTENT] = $loggingConfig->serializeToXml();
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Deletes the bucket logging config
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws 
     * @return array
     */
    public function deleteBucketLogging($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_DELETE;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'logging';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Sets the website config in bucket---that is could make the bucket as a static website once the CName is binded.
     *
     * @param string $bucket bucket name
     * @param WebsiteConfig $websiteConfig
     * @param array $options
     * @throws 
     * @return array
     */
    public function putBucketWebsite($bucket, $websiteConfig, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'website';
        $options[self::OOS_CONTENT_TYPE] = 'application/xml';
        $options[self::OOS_CONTENT] = $websiteConfig->serializeToXml();
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Gets the website config in the bucket
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws 
     * @return WebsiteConfig
     */
    public function getBucketWebsite($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'website';
        $response = $this->auth($options);
        $result = new GetWebsiteResult($response);
        return $result->getData();
    }

    /**
     * Deletes the website config in the bucket
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws 
     * @return array
     */
    public function deleteBucketWebsite($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_DELETE;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'website';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Sets the cross-origin-resource-sharing (CORS) rule. It would overwrite the originl one.
     *
     * @param string $bucket bucket name
     * @param CorsConfig $corsConfig CORS config. Check out the details from OOS API document
     * @param array $options array
     * @throws 
     * @return array
     */
    public function putBucketCors($bucket, $corsConfig, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'cors';
        $options[self::OOS_CONTENT_TYPE] = 'application/xml';
        $options[self::OOS_CONTENT] = $corsConfig->serializeToXml();
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Gets the bucket CORS config
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws 
     * @return CorsConfig
     */
    public function getBucketCors($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'cors';
        $response = $this->auth($options);
        $result = new GetCorsResult($response, __FUNCTION__);
        return $result->getData();
    }

    /**
     * Deletes the bucket's CORS config and disable the CORS on the bucket.
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws 
     * @return array
     */
    public function deleteBucketCors($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_DELETE;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'cors';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Sets the policy. It would overwrite the originl one.
     *
     * @param string $bucket bucket name
     * @param CorsConfig $corsConfig CORS config. Check out the details from OOS API document
     * @param array $options array
     * @throws 
     * @return array
     */
    public function putBucketPolicy($bucket, $textPolicy, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'policy';
        $options[self::OOS_CONTENT_TYPE] = 'text/plain';
        $options[self::OOS_CONTENT] = $textPolicy;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Gets the bucket policy
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws  
     * @return string
     */
    public function getBucketPolicy($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'policy';
        $response = $this->auth($options);
        $result = new GetPolicyResult($response);
        return $result->parseDataFromResponse();
    }

    /**
     * Deletes the bucket's policy.
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws 
     * @return array
     */
    public function deleteBucketPolicy($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_DELETE;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'policy';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Sets a bucket's Accelerate, which has a whitelist of referrer and specifies if empty referer is allowed.
     * Checks out API document for more details about "Bucket Referer"
     *
     * @param string $bucket bucket name
     * @param AccelerateConfig $accelerateConfig
     * @param array $options
     * @return ResponseCore
     * @throws
     */
    public function putBucketAccelerate($bucket, $accelerateConfig, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'accelerate';
        $options[self::OOS_CONTENT_TYPE] = 'application/xml';
        $options[self::OOS_CONTENT] = $accelerateConfig->serializeToXml();
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Gets the bucket's Referer
     * Checks out API document for more details about "Bucket Referer"
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws 
     * @return AccelerateConfig
     */
    public function getBucketAccelerate($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'accelerate';
        $response = $this->auth($options);
        $result = new GetAccelerateResult($response);
        return $result->getData();
    }

    /**Get the data regions and metadata regions of the specified user.
     * This method is only used for Object Storage Network,
     * the other resource pools can not use this method.
     * @param array $options
     * @throws
     * @return BucketRegion
    */
    function getRegions($options = NULL)
    {
        //判断是否是S5的endpoint
        if($this->isV5Server){
            $excpetionMsg = "This method can not be used for endpoint: " . $this->endpoint . " \n";
            throw new \Exception($excpetionMsg);
            return;
        }
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_SUB_RESOURCE] = 'regions';
        $response = $this->auth($options);
        $result = new RegionsResult($response);
        return $result->getData();
    }

    /**
     * Precheck the CORS request. Before sending a CORS request, a preflight request (OPTIONS) is sent with the specific origin.
     * HTTP METHOD and headers information are sent to OOS as well for evaluating if the CORS request is allowed.
     * 
     * Note: OOS could enable the CORS on the bucket by calling putBucketCors. Once CORS is enabled, the OOS could evaluate accordingto the preflight request.
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $origin the origin of the request
     * @param string $request_method The actual HTTP method which will be used in CORS request
     * @param string $request_headers The actual HTTP headers which will be used in CORS request
     * @param array $options
     * @return array
     * @throws
     */
    public function optionsObject($bucket, $object, $origin, $request_method, $request_headers, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_OPTIONS;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_HEADERS] = array(
            self::OOS_OPTIONS_ORIGIN => $origin,
            self::OOS_OPTIONS_REQUEST_HEADERS => $request_headers,
            self::OOS_OPTIONS_REQUEST_METHOD => $request_method
        );
        $response = $this->auth($options);
        $result = new HeaderResult($response);
        return $result->getData();
    }

    /**
     * Sets the bucket's lifecycle config
     *
     * @param string $bucket bucket name
     * @param LifecycleConfig $lifecycleConfig LifecycleConfig instance
     * @param array $options
     * @throws
     * @return array
     */
    public function putBucketLifecycle($bucket, $lifecycleConfig, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'lifecycle';
        $options[self::OOS_CONTENT_TYPE] = 'application/xml';
        $options[self::OOS_CONTENT] = $lifecycleConfig->serializeToXml();
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Gets bucket's lifecycle config
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws
     * @return LifecycleConfig
     */
    public function getBucketLifecycle($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'lifecycle';
        $response = $this->auth($options);
        $result = new GetLifecycleResult($response);
        return $result->getData();
    }

    /**
     * Deletes the bucket's lifecycle config
     *
     * @param string $bucket bucket name
     * @param array $options
     * @throws
     * @return array
     */
    public function deleteBucketLifecycle($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_DELETE;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'lifecycle';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Lists the bucket's object list (in ObjectListInfo)
     *
     * @param string $bucket
     * @param array $options are defined below:
     * $options = array(
     *      'max-keys'  => specifies max object count to return. By default is 100 and max value could be 1000.
     *      'prefix'    => specifies the key prefix the returned objects must have. Note that the returned keys still contain the prefix.
     *      'delimiter' => The delimiter of object name for grouping object. When it's specified, listObjects will differeniate the object and folder. And it will return subfolder's objects.
     *      'marker'    => The key of returned object must be greater than the 'marker'.
     *)
     * Prefix and marker are for filtering and paging. Their length must be less than 256 bytes
     * @throws
     * @return ObjectListInfo
     */
    public function listObjects($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_QUERY_STRING] = array();

        if(isset($options[self::OOS_DELIMITER]))
        {
            if((string)$options[self::OOS_DELIMITER] !== "/")
                throw new OosException("delimiter must be '/'");
            $options[self::OOS_QUERY_STRING][self::OOS_DELIMITER] = $options[self::OOS_DELIMITER];
            unset($options[self::OOS_DELIMITER]);
        }

        foreach (array( self::OOS_PREFIX, self::OOS_MAX_KEYS, self::OOS_MARKER) as $param) {
            if (isset($options[$param])) {
                $options[self::OOS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }
        $query = isset($options[self::OOS_QUERY_STRING]) ? $options[self::OOS_QUERY_STRING] : array();

        $response = $this->auth($options);

        $result = new ListObjectsResult($response);
        return $result->getData();
    }

    /**
     * Creates a virtual 'folder' in OOS. The name should not end with '/' because the method will append the name with a '/' anyway.
     *
     * Internal use only.
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param array $options
     * @throws
     * @return null
     */
    public function createObjectDir($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = $object . '/';
        $options[self::OOS_CONTENT_LENGTH] = array(self::OOS_CONTENT_LENGTH => 0);
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Uploads the $content object to OOS.
     *
     * @param string $bucket bucket name
     * @param string $object objcet name
     * @param string $content The content object
     * @param array $options
     * @param DataLocation $dataLocation
     * @throws
     * @return null
     */
    public function putObject($bucket, $object, $content, $options = NULL,$dataLocation = NULL)
    {
        if(!isset($options) || $options == NULL){
            $options  = array();
        }
        $this->precheckCommon($bucket, $object, $options);

        $options[self::OOS_CONTENT] = $content;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = $object;

        if (!isset($options[self::OOS_LENGTH])) {
            $options[self::OOS_CONTENT_LENGTH] = strlen($options[self::OOS_CONTENT]);
        } else {
            $options[self::OOS_CONTENT_LENGTH] = $options[self::OOS_LENGTH];
        }

        //$is_check_md5 = true;
        $is_check_md5 = true;
        if ($is_check_md5) {
            $content_md5 = base64_encode(md5($content, true));
            $options[self::OOS_CONTENT_MD5] = $content_md5;
        }

        if (!isset($options[self::OOS_CONTENT_TYPE])) {
            $options[self::OOS_CONTENT_TYPE] = $this->getMimeType($object);
        }
        //设置bucket的数据位置,类型：key-value形式
        if (isset($dataLocation)) {
            if($this->isV5Server){
                throw new OosException( 'The data location of the object. This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.');
            }
            $keyValueString = $dataLocation->toKeyValueString();
            $options[self::OOS_CTYUN_DATA_LOCATION] = $keyValueString;
        }

        if (!isset($options[self::OOS_CONTENT_TYPE])) {
            $options[self::OOS_CONTENT_TYPE] = $this->getMimeType($object);
        }

        $response = $this->auth($options);

        if (isset($options[self::OOS_CALLBACK]) && !empty($options[self::OOS_CALLBACK])) {
            $result = new CallbackResult($response);
        } else {
            $result = new PutSetDeleteResult($response);
        }

        return $result->getData();
    }

    /**
     * Uploads a local file
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $file local file path
     * @param array $options
     * @return null
     * @throws
     */
    public function uploadFile($bucket, $object, $file, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        OosUtil::throwOssExceptionWithMessageIfEmpty($file, "file path is invalid");
        $file = OosUtil::encodePath($file);
        if (!file_exists($file)) {
            throw new OosException($file . " file does not exist");
        }
        $options[self::OOS_FILE_UPLOAD] = $file;
        $file_size = filesize($options[self::OOS_FILE_UPLOAD]);
        //$is_check_md5 = true;
        $is_check_md5 = true;
        if ($is_check_md5) {
            $content_md5 = base64_encode(md5_file($options[self::OOS_FILE_UPLOAD], true));
            $options[self::OOS_CONTENT_MD5] = $content_md5;
        }
        if (!isset($options[self::OOS_CONTENT_TYPE])) {
            $options[self::OOS_CONTENT_TYPE] = $this->getMimeType($object, $file);
        }
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_CONTENT_LENGTH] = $file_size;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Append the object with the content at the specified position.
     * The specified position is typically the lengh of the current file.
     * @param string $bucket bucket name
     * @param string $object objcet name
     * @param string $content content to append
     * @param array $options
     * @return int next append position
     * @throws
     */
    public function appendObject($bucket, $object, $content, $position, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);

        $options[self::OOS_CONTENT] = $content;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_POST;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_SUB_RESOURCE] = 'append';
        $options[self::OOS_POSITION] = strval($position);

        if (!isset($options[self::OOS_LENGTH])) {
            $options[self::OOS_CONTENT_LENGTH] = strlen($options[self::OOS_CONTENT]);
        } else {
            $options[self::OOS_CONTENT_LENGTH] = $options[self::OOS_LENGTH];
        }
        
        $is_check_md5 = true;
        if ($is_check_md5) {
        	$content_md5 = base64_encode(md5($content, true));
        	$options[self::OOS_CONTENT_MD5] = $content_md5;
        }

        if (!isset($options[self::OOS_CONTENT_TYPE])) {
            $options[self::OOS_CONTENT_TYPE] = $this->getMimeType($object);
        }
        $response = $this->auth($options);
        $result = new AppendResult($response);
        return $result->getData();
    }

    /**
     * Append the object with a local file
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $file The local file path to append with
     * @param array $options
     * @return int next append position
     * @throws
     */
    public function appendFile($bucket, $object, $file, $position, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);

        OosUtil::throwOssExceptionWithMessageIfEmpty($file, "file path is invalid");
        $file = OosUtil::encodePath($file);
        if (!file_exists($file)) {
            throw new OosException($file . " file does not exist");
        }
        $options[self::OOS_FILE_UPLOAD] = $file;
        $file_size = filesize($options[self::OOS_FILE_UPLOAD]);
        //$is_check_md5 = true;
        $is_check_md5 = true;
        if ($is_check_md5) {
            $content_md5 = base64_encode(md5_file($options[self::OOS_FILE_UPLOAD], true));
            $options[self::OOS_CONTENT_MD5] = $content_md5;
        }
        if (!isset($options[self::OOS_CONTENT_TYPE])) {
            $options[self::OOS_CONTENT_TYPE] = $this->getMimeType($object, $file);
        }

        $options[self::OOS_METHOD] = self::OOS_HTTP_POST;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_CONTENT_LENGTH] = $file_size;
        $options[self::OOS_SUB_RESOURCE] = 'append';
        $options[self::OOS_POSITION] = strval($position);

        $response = $this->auth($options);
        $result = new AppendResult($response);
        return $result->getData();
    }

    /**
     * Copy from an existing OOS object to another OOS object. If the target object exists already, it will be overwritten.
     *
     * @param string $fromBucket Source bucket name
     * @param string $fromObject Source object name
     * @param string $toBucket Target bucket name
     * @param string $toObject Target object name
     * @param array $options
     * @param DataLocation $dataLocation
     * @return CopyPartInfo
     * @throws
     */
    public function copyObject($fromBucket, $fromObject, $toBucket, $toObject, $options = NULL,$dataLocation = NULL)
    {
        $this->precheckCommon($fromBucket, $fromObject, $options);
        $this->precheckCommon($toBucket, $toObject, $options);
        $options[self::OOS_BUCKET] = $toBucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_OBJECT] = $toObject;

        //设置数据位置,类型：key-value形式
        if (isset($dataLocation)) {
            if($this->isV5Server){
                throw new OosException( 'The data location of the object. This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.');
            }
            $keyValueString = $dataLocation->toKeyValueString();
            $options[self::OOS_CTYUN_DATA_LOCATION] = $keyValueString;
        }

        if (isset($options[self::OOS_HEADERS])) {
            $options[self::OOS_HEADERS][self::OOS_OBJECT_COPY_SOURCE] = '/' . $fromBucket . '/' . $fromObject;
        } else {
            $options[self::OOS_HEADERS] = array(self::OOS_OBJECT_COPY_SOURCE => '/' . $fromBucket . '/' . $fromObject);
        }
        $response = $this->auth($options);
        $result = new CopyObjectResult($response);
        return $result->getData();
    }

    /**
     * Copy an existing file as a part
     *
     * @param string $fromBucket source bucket name
     * @param string $fromObject source object name
     * @param string $toBucket target bucket name
     * @param string $toObject target object name
     * @param int $toPartNumber Part number
     * @param string $toUploadId Upload Id
     * @param array $options Key-Value array---it should have 'start' or 'end' key to specify the range of the source object to copy. If it's not specifed, the whole object is copied.
     * @return CopyPartInfo
     * @throws
     */
    public function copyObjectPart($fromBucket,$fromObject,$toBucket,$toObject,$toPartNumber,$toUploadId,$options = NULL,$dataLocation = NULL)
    {
        $this->precheckCommon($fromBucket, $fromObject, $options);
        $this->precheckCommon($toBucket, $toObject, $options);

        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;

        $options[self::OOS_BUCKET] = $toBucket;
        $options[self::OOS_OBJECT] = $toObject;
        $options[self::OOS_PART_NUM] = $toPartNumber;
        $options[self::OOS_UPLOAD_ID] = $toUploadId;

        //设置数据位置,类型：key-value形式
        if (isset($dataLocation)) {
            if($this->isV5Server){
                throw new OosException( 'The data location of the object. This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.');
            }
            $keyValueString = $dataLocation->toKeyValueString();
            $options[self::OOS_CTYUN_DATA_LOCATION] = $keyValueString;
        }

        if (isset($options[self::OOS_HEADERS])) {
            $options[self::OOS_HEADERS][self::OOS_OBJECT_COPY_SOURCE] = '/' . $fromBucket . '/' . $fromObject;
        } else {
            $options[self::OOS_HEADERS] = array(self::OOS_OBJECT_COPY_SOURCE => '/' . $fromBucket . '/' . $fromObject);
        }

        $start_range = "0";
        if (isset($options['start'])) {
            $start_range = $options['start'];
        }
        $end_range = "";
        if (isset($options['end'])) {
            $end_range = $options['end'];
        }

        if (isset($options['start']) && isset($options['end'])) {
            $options[self::OOS_HEADERS][self::OOS_OBJECT_COPY_SOURCE_RANGE] = "bytes=" . $start_range . "-" . $end_range;
        }

        $response = $this->auth($options);
        $result = new CopyObjectResult($response);
        return $result->getData();
    }

    /**
     * Gets Object metadata
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $options Checks out the SDK document for the detail
     * @throws
     * @return ObjectInfo;
     */
    public function headObject($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_HEAD;
        $options[self::OOS_OBJECT] = $object;
        $response = $this->auth($options);
        $result = new HeaderObjectResult($response);
        $objectInfo = $result->getData();
        $objectInfo->setKey($object);
        return $objectInfo;
    }

    /**
     * Deletes a object
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param array $options
     * @return null
     * @throws
     */
    public function deleteObject($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_DELETE;
        $options[self::OOS_OBJECT] = $object;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Deletes multiple objects in a bucket
     *
     * @param string $bucket bucket name
     * @param array $objects object list
     * @param array $options
     * @return ResponseCore
     * @throws null
     */
    public function deleteObjects($bucket, $objects, $options = null)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        if (!is_array($objects) || !$objects) {
            throw new OosException('objects must be array');
        }
        $options[self::OOS_METHOD] = self::OOS_HTTP_POST;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'delete';
        $options[self::OOS_CONTENT_TYPE] = 'application/xml';
        $quiet = NUll;
        if (isset($options['quiet'])) {
            if (is_bool($options['quiet'])) { //Boolean
                $quiet = $options['quiet'] ? 'true' : 'false';
            } elseif (is_string($options['quiet'])) { // string
                $quiet = ($options['quiet'] === 'true') ? 'true' : 'false';
            }
        }
        $xmlBody = OosUtil::createDeleteObjectsXmlBody($objects, $quiet);
        $options[self::OOS_CONTENT] = $xmlBody;
        $response = $this->auth($options);
        $result = new DeleteObjectsResult($response);
        return $result->getData();
    }

    /**
     * Gets Object content
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param array $options It must contain ALIOSS::OOS_FILE_DOWNLOAD. And ALIOSS::OOS_RANGE is optional and empty means to download the whole file.
     * @return GetObjectInfo
     * @throws
     */
    public function getObject($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_OBJECT] = $object;
        if (isset($options[self::OOS_LAST_MODIFIED])) {
            $options[self::OOS_HEADERS][self::OOS_IF_MODIFIED_SINCE] = $options[self::OOS_LAST_MODIFIED];
            unset($options[self::OOS_LAST_MODIFIED]);
        }
        if (isset($options[self::OOS_ETAG])) {
            $options[self::OOS_HEADERS][self::OOS_IF_NONE_MATCH] = $options[self::OOS_ETAG];
            unset($options[self::OOS_ETAG]);
        }
        if (isset($options[self::OOS_RANGE])) {
            $range = $options[self::OOS_RANGE];
            $options[self::OOS_HEADERS][self::OOS_RANGE] = "$range";
            unset($options[self::OOS_RANGE]);
        }
        $response = $this->auth($options);
        $result = new GetObjectResult($response);
        return $result->parseDataFromResponse();
    }

    /**
     * Checks if the object exists
     * It's implemented by getObjectMeta().
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param array $options
     * @return bool True:object exists; False:object does not exist
     * @throws
     */
    public function doesObjectExist($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_METHOD] = self::OOS_HTTP_HEAD;
        $options[self::OOS_OBJECT] = $object;
        $response = $this->auth($options);
        $result = new ExistResult($response);
        return $result->getData();
    }

    /**
     * Gets the part size according to the preferred part size.
     * If the specified part size is too small or too big, it will return a min part or max part size instead.
     * Otherwise returns the specified part size.
     * @param int $partSize
     * @return int
     */
    private function computePartSize($partSize)
    {
        $partSize = (integer)$partSize;
        if ($partSize <= self::OOS_MIN_PART_SIZE) {
            $partSize = self::OOS_MIN_PART_SIZE;
        } elseif ($partSize > self::OOS_MAX_PART_SIZE) {
            $partSize = self::OOS_MAX_PART_SIZE;
        }
        return $partSize;
    }

    /**
     * Computes the parts count, size and start position according to the file size and the part size.
     * It must be only called by upload_Part().
     *
     * @param integer $file_size File size
     * @param integer $partSize part大小,part size. Default is 5MB
     * @return array An array contains key-value pairs--the key is `seekTo`and value is `length`.
     */
    public function generateMultiuploadParts($file_size, $partSize = 5242880)
    {
        $i = 0;
        $size_count = $file_size;
        $values = array();
        $partSize = $this->computePartSize($partSize);
        while ($size_count > 0) {
            $size_count -= $partSize;
            $values[] = array(
                self::OOS_SEEK_TO => ($partSize * $i),
                self::OOS_LENGTH => (($size_count > 0) ? $partSize : ($size_count + $partSize)),
            );
            $i++;
        }
        return $values;
    }

    /**
     * Initialize a multi-part upload
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param array $options Key-Value array
     * @throws
     * @return InitiateMultipartUploadInfo
     */
    public function initiateMultipartUpload($bucket, $object, $options = NULL,$dataLocation = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OOS_METHOD] = self::OOS_HTTP_POST;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_SUB_RESOURCE] = 'uploads';
        $options[self::OOS_CONTENT] = '';

        //设置bucket的数据位置,类型：key-value形式
        if (isset($dataLocation)) {
            if($this->isV5Server){
                throw new OosException( 'The data location of the object. This parameter is only used for Object Storage Network, the other resource pools can not use this parameter.');
            }
            $keyValueString = $dataLocation->toKeyValueString();
            $options[self::OOS_CTYUN_DATA_LOCATION] = $keyValueString;
        }

        if (!isset($options[self::OOS_CONTENT_TYPE])) {
            $options[self::OOS_CONTENT_TYPE] = $this->getMimeType($object);
        }
        if (!isset($options[self::OOS_HEADERS])) {
            $options[self::OOS_HEADERS] = array();
        }
        $response = $this->auth($options);
        $result = new InitiateMultipartUploadResult($response);
        return $result->getData();
    }

    /**
     * Upload a part in a multiparts upload.
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $uploadId
     * @param array $options Key-Value array
     * @return string eTag
     * @throws
     */
    public function uploadPart($bucket, $object, $uploadId, $options = null)
    {
        $this->precheckCommon($bucket, $object, $options);
        $this->precheckParam($options, self::OOS_FILE_UPLOAD, __FUNCTION__);
        $this->precheckParam($options, self::OOS_PART_NUM, __FUNCTION__);

        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_UPLOAD_ID] = $uploadId;

        if (isset($options[self::OOS_LENGTH])) {
            $options[self::OOS_CONTENT_LENGTH] = $options[self::OOS_LENGTH];
        }
        $response = $this->auth($options);
        $result = new UploadPartResult($response);
        return $result->getData();
    }

    /**
     * Gets the uploaded parts.
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $uploadId uploadId
     * @param array $options Key-Value array
     * @return ListPartsInfo
     * @throws
     */
    public function listParts($bucket, $object, $uploadId, $options = null)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_UPLOAD_ID] = $uploadId;
        $options[self::OOS_QUERY_STRING] = array();
        foreach (array('max-parts', 'part-number-marker') as $param) {
            if (isset($options[$param])) {
                $options[self::OOS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }
        $response = $this->auth($options);
        $result = new ListPartsResult($response);
        return $result->getData();
    }

    /**
     * Abort a multiparts upload
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $uploadId uploadId
     * @param array $options Key-Value name
     * @return null
     * @throws
     */
    public function abortMultipartUpload($bucket, $object, $uploadId, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OOS_METHOD] = self::OOS_HTTP_DELETE;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_UPLOAD_ID] = $uploadId;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * Completes a multiparts upload, after all parts are uploaded.
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $uploadId uploadId
     * @param array $listParts array( array("PartNumber"=> int, "ETag"=>string))
     * @param array $options Key-Value array
     * @throws
     * @return CompleteMultipartUploadInfo
     */
    public function completeMultipartUpload($bucket, $object, $uploadId, $listParts, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::OOS_METHOD] = self::OOS_HTTP_POST;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_UPLOAD_ID] = $uploadId;
        $options[self::OOS_CONTENT_TYPE] = 'application/xml';
        $options[self::OOS_CONTENT_NO_PAYLOAD] = self::OOS_CONTENT_NO_PAYLOAD;
        if (!is_array($listParts)) {
            throw new OosException("listParts must be array type");
        }
        $options[self::OOS_CONTENT] = OosUtil::createCompleteMultipartUploadXmlBody($listParts);
        $response = $this->auth($options);
        if (isset($options[self::OOS_CALLBACK]) && !empty($options[self::OOS_CALLBACK])) {
            $result = new CallbackResult($response);
        } else {
            $result = new CompleteMultipartUploadResult($response);
        }
        return $result->getData();
    }

    /**
     * Lists all ongoing multipart upload events, which means all initialized but not completed or aborted multipart uploads.
     *
     * @param string $bucket bucket
     * @param array $options key-value array--expected keys are 'delimiter', 'key-marker', 'max-uploads', 'prefix', 'upload-id-marker'
     * @throws
     * @return ListMultipartUploadInfo
     */
    public function listMultipartUploads($bucket, $options = null)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::OOS_METHOD] = self::OOS_HTTP_GET;
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = '/';
        $options[self::OOS_SUB_RESOURCE] = 'uploads';

        foreach (array('delimiter', 'key-marker', 'max-uploads', 'prefix', 'upload-id-marker') as $param) {
            if (isset($options[$param])) {
                $options[self::OOS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }
        $query = isset($options[self::OOS_QUERY_STRING]) ? $options[self::OOS_QUERY_STRING] : array();

        $response = $this->auth($options);
        $result = new ListMultipartUploadResult($response);
        return $result->getData();
    }

    /**
     * Copy an existing file as a part
     *
     * @param string $fromBucket source bucket name
     * @param string $fromObject source object name
     * @param string $toBucket target bucket name
     * @param string $toObject target object name
     * @param int $partNumber Part number
     * @param string $uploadId Upload Id
     * @param array $options Key-Value array---it should have 'start' or 'end' key to specify the range of the source object to copy. If it's not specifed, the whole object is copied.
     * @return null
     * @throws
     */
    public function uploadPartCopy($fromBucket, $fromObject, $toBucket, $toObject, $partNumber, $uploadId, $options = NULL)
    {
        $this->precheckCommon($fromBucket, $fromObject, $options);
        $this->precheckCommon($toBucket, $toObject, $options);

        //If $options['isFullCopy'] is not set, copy from the beginning
        $start_range = "0";
        if (isset($options['start'])) {
            $start_range = $options['start'];
        }
        $end_range = "";
        if (isset($options['end'])) {
            $end_range = $options['end'];
        }
        $options[self::OOS_METHOD] = self::OOS_HTTP_PUT;
        $options[self::OOS_BUCKET] = $toBucket;
        $options[self::OOS_OBJECT] = $toObject;
        $options[self::OOS_PART_NUM] = $partNumber;
        $options[self::OOS_UPLOAD_ID] = $uploadId;

        if (!isset($options[self::OOS_HEADERS])) {
            $options[self::OOS_HEADERS] = array();
        }

        $options[self::OOS_HEADERS][self::OOS_OBJECT_COPY_SOURCE] = '/' . $fromBucket . '/' . $fromObject;
        $options[self::OOS_HEADERS][self::OOS_OBJECT_COPY_SOURCE_RANGE] = "bytes=" . $start_range . "-" . $end_range;
        $response = $this->auth($options);
        $result = new UploadPartResult($response);
        return $result->getData();
    }

    /**
     * A higher level API for uploading a file with multipart upload. It consists of initialization,
     * parts upload and completion.
     *
     * @param string $bucket bucket name
     * @param string $object object name
     * @param string $file The local file to upload
     * @param array $options Key-Value array
     * @return CompleteMultipartUploadInfo
     * @throws 
     */
    public function multiuploadFile($bucket, $object, $file, $options = null)
    {
        $this->precheckCommon($bucket, $object, $options);
        if (isset($options[self::OOS_LENGTH])) {
            $options[self::OOS_CONTENT_LENGTH] = $options[self::OOS_LENGTH];
            unset($options[self::OOS_LENGTH]);
        }
        if (empty($file)) {
            throw new OosException("parameter invalid, file is empty");
        }
        $uploadFile = OosUtil::encodePath($file);
        if (!isset($options[self::OOS_CONTENT_TYPE])) {
            $options[self::OOS_CONTENT_TYPE] = $this->getMimeType($object, $uploadFile);
        }

        $upload_position = isset($options[self::OOS_SEEK_TO]) ? (integer)$options[self::OOS_SEEK_TO] : 0;

        if (isset($options[self::OOS_CONTENT_LENGTH])) {
            $upload_file_size = (integer)$options[self::OOS_CONTENT_LENGTH];
        } else {
            $upload_file_size = filesize($uploadFile);
            if ($upload_file_size !== false) {
                $upload_file_size -= $upload_position;
            }
        }

        if ($upload_position === false || !isset($upload_file_size) || $upload_file_size === false || $upload_file_size < 0) {
            throw new OosException('The size of `fileUpload` cannot be determined in ' . __FUNCTION__ . '().');
        }

        // Computes the part size and assign it to options.
        if (isset($options[self::OOS_PART_SIZE])) {
            $options[self::OOS_PART_SIZE] = $this->computePartSize($options[self::OOS_PART_SIZE]);
        } else {
            $options[self::OOS_PART_SIZE] = self::OOS_MID_PART_SIZE;
        }

        //$is_check_md5 = true;
        $is_check_md5 = true;
        // if the file size is less than part size, use simple file upload.
        if ($upload_file_size < $options[self::OOS_PART_SIZE] && !isset($options[self::OOS_UPLOAD_ID])) {
            return $this->uploadFile($bucket, $object, $uploadFile, $options);
        }

        // Using multipart upload, initialize if no OOS_UPLOAD_ID is specified in options.
        if (isset($options[self::OOS_UPLOAD_ID])) {
            $uploadId = $options[self::OOS_UPLOAD_ID];
        } else {
            // initialize
            $uploadId = $this->initiateMultipartUpload($bucket, $object, $options)->getUploadId();
        }

        // generates the parts information and upload them one by one
        $pieces = $this->generateMultiuploadParts($upload_file_size, (integer)$options[self::OOS_PART_SIZE]);
        $response_upload_part = array();
        foreach ($pieces as $i => $piece) {
            $from_pos = $upload_position + (integer)$piece[self::OOS_SEEK_TO];
            $to_pos = (integer)$piece[self::OOS_LENGTH] + $from_pos - 1;
            $up_options = array(
                self::OOS_FILE_UPLOAD => $uploadFile,
                self::OOS_PART_NUM => ($i + 1),
                self::OOS_SEEK_TO => $from_pos,
                self::OOS_LENGTH => $to_pos - $from_pos + 1,
                self::OOS_CHECK_MD5 => $is_check_md5,
            );
            if ($is_check_md5) {
                $content_md5 = OosUtil::getMd5SumForFile($uploadFile, $from_pos, $to_pos);
                $up_options[self::OOS_CONTENT_MD5] = $content_md5;
            }
            $response_upload_part[] = $this->uploadPart($bucket, $object, $uploadId, $up_options);
        }

        $uploadParts = array();
        foreach ($response_upload_part as $i => $etag) {
            $uploadParts[] = array(
                'PartNumber' => ($i + 1),
                'ETag' => $etag,
            );
        }
        return $this->completeMultipartUpload($bucket, $object, $uploadId, $uploadParts);
    }

    /**
     * Uploads the local directory to the specified bucket into specified folder (prefix)
     *
     * @param string $bucket bucket name
     * @param string $prefix The object key prefix. Typically it's folder name. The name should not end with '/' as the API appends it automatically.
     * @param string $localDirectory The local directory to upload
     * @param string $exclude To excluded directories
     * @param bool $recursive Recursive flag. True: Recursively upload all datas under the local directory; False: only upload first layer's files.
     * @param bool $checkMd5
     * @return array Returns two list: array("succeededList" => array("object"), "failedList" => array("object"=>"errorMessage"))
     * @throws 
     */
    public function uploadDir($bucket, $prefix, $localDirectory, $exclude = '.|..|.svn|.git', $recursive = false, $checkMd5 = true)
    {
        $retArray = array("succeededList" => array(), "failedList" => array());
        if (empty($bucket)) throw new OosException("parameter error, bucket is empty");
        if (!is_string($prefix)) throw new OosException("parameter error, prefix is not string");
        if (empty($localDirectory)) throw new OosException("parameter error, localDirectory is empty");
        $directory = $localDirectory;
        $directory = OosUtil::encodePath($directory);
        //If it's not the local directory, throw OSSException.
        if (!is_dir($directory)) {
            throw new OosException('parameter error: ' . $directory . ' is not a directory, please check it');
        }
        //read directory
        $file_list_array = OosUtil::readDir($directory, $exclude, $recursive);
        if (!$file_list_array) {
            throw new OosException($directory . ' is empty...');
        }
        foreach ($file_list_array as $k => $item) {
            if (is_dir($item['path'])) {
                continue;
            }
            $options = array(
                self::OOS_PART_SIZE => self::OOS_MIN_PART_SIZE,
                self::OOS_CHECK_MD5 => $checkMd5,
            );
            $realObject = (!empty($prefix) ? $prefix . '/' : '') . $item['file'];

            try {
                $this->multiuploadFile($bucket, $realObject, $item['path'], $options);
                $retArray["succeededList"][] = $realObject;
            } catch (OosException $e) {
                $retArray["failedList"][$realObject] = $e->getMessage();
            }
        }
        return $retArray;
    }

    /**
     * Create AccessKey
     * @param array $options Key-Value array
     * @return KeyInfo
     * @throws
     */
    public function CreateAccessKey($options = NULL)
    {
        $this->precheckOptions($options);
        $options[self::OOS_METHOD] = self::OOS_HTTP_POST;
        $options[self::OOS_SERVICE] = 'sts';

        if (!isset($options[self::OOS_HEADERS])) {
            $options[self::OOS_HEADERS] = array();
        }
        $content = self::ACCESS_KEY_ACTION . "=" . self::CREATE_ACCESS_KEY;
        $options[self::OOS_CONTENT] = $content;
        $options[self::OOS_CONTENT_LENGTH] = strlen($content);
        $options[self::OOS_CONTENT_NO_PAYLOAD] = self::OOS_CONTENT_NO_PAYLOAD;
        $options[self::OOS_CONTENT_TYPE] = "application/x-www-form-urlencoded; charset=utf-8";
        $response = $this->auth($options);
        $result = new CreateAccessKeyResult($response);
        return $result->getData();
    }

    /**
     * Delete AccessKey
     * @param String $accessKeyId
     * @param array $options Key-Value array
     * @return DeleteUpdateAccessKeyInfo
     * @throws
     */
    public function DeleteAccessKey($accessKeyId,$options = NULL)
    {
        $this->precheckOptions($options);
        $options[self::OOS_METHOD] = self::OOS_HTTP_POST;
        $options[self::OOS_SERVICE] = 'sts';

        if (!isset($options[self::OOS_HEADERS])) {
            $options[self::OOS_HEADERS] = array();
        }
        $options[self::OOS_CONTENT] = self::ACCESS_KEY_ACTION . "=" . self::DELETE_ACCESS_KEY
            ."&" . self::ACCESS_KEY_ID . "=" . $accessKeyId;

        $options[self::OOS_CONTENT_NO_PAYLOAD] = self::OOS_CONTENT_NO_PAYLOAD;
        $options[self::OOS_CONTENT_TYPE] = "application/x-www-form-urlencoded; charset=utf-8";
        $response = $this->auth($options);
        $result = new DeleteUpdateAccessKeyResult($response);
        return $result->getData();
    }

    /**
     * Update AccessKey
     * @param String $accessKeyId
     * @param String $status
     * @param String $isPrimary
     * @param array $options Key-Value array
     * @return DeleteUpdateAccessKeyInfo
     * @throws
     */
    public function UpdateAccessKey($accessKeyId,$status,$isPrimary,$options = NULL)
    {
        $this->precheckOptions($options);
        $options[self::OOS_METHOD] = self::OOS_HTTP_POST;
        $options[self::OOS_SERVICE] = 'sts';

        if (!isset($options[self::OOS_HEADERS])) {
            $options[self::OOS_HEADERS] = array();
        }
        $options[self::OOS_CONTENT] = self::ACCESS_KEY_ACTION . "=" . self::UPDATE_ACCESS_KEY
            ."&" . self::ACCESS_KEY_ID . "=" . $accessKeyId
            ."&" . self::ACCESS_KEY_STATUS . "=" . $status
            ."&" . self::ACCESS_KEY_IS_PRIMARY . "=" . $isPrimary;

        $options[self::OOS_CONTENT_NO_PAYLOAD] = self::OOS_CONTENT_NO_PAYLOAD;
        $options[self::OOS_CONTENT_TYPE] = "application/x-www-form-urlencoded; charset=utf-8";
        $response = $this->auth($options);
        $result = new DeleteUpdateAccessKeyResult($response);
        return $result->getData();
    }

    /**
     * List AccessKey
     * @param int $MaxItems
     * @param String $Marker
     * @param array $options Key-Value array
     * @return ListKeyInfo
     * @throws
     */
    public function ListAccessKey($MaxItems,$Marker,$options = NULL)
    {
        $this->precheckOptions($options);
        $options[self::OOS_METHOD] = self::OOS_HTTP_POST;
        $options[self::OOS_SERVICE] = 'sts';

        if (!isset($options[self::OOS_HEADERS])) {
            $options[self::OOS_HEADERS] = array();
        }

        if(isset($Marker) && strlen($Marker)>0){
            $options[self::OOS_CONTENT] = self::ACCESS_KEY_ACTION . "=" . self::LIST_ACCESS_KEY
                ."&" . self::ACCESS_KEY_MAXITEM . "=" . $MaxItems
                ."&" . self::ACCESS_KEY_MARKER . "=" . $Marker;
        }
        else{
            $options[self::OOS_CONTENT] = self::ACCESS_KEY_ACTION . "=" . self::LIST_ACCESS_KEY
                ."&" . self::ACCESS_KEY_MAXITEM . "=" . $MaxItems;
        }

        $options[self::OOS_CONTENT_NO_PAYLOAD] = self::OOS_CONTENT_NO_PAYLOAD;
        $options[self::OOS_CONTENT_TYPE] = "application/x-www-form-urlencoded; charset=utf-8";
        $response = $this->auth($options);
        $result = new ListAccessKeyResult($response);
        return $result->getData();
    }

    /**
     * Sign URL with specified expiration time in seconds (timeout) and HTTP method.
     * The signed URL could be used to access the object directly.
     *
     * @param string $bucket
     * @param string $object
     * @param int $timeout expiration time in seconds.
     * @param string $method
     * @param array $options Key-Value array
     * @return string
     * @throws
     */
    public function signUrl($bucket, $object, $timeout = 1200, $method = self::OOS_HTTP_GET, $options = NULL)
    {
        if(!isset($options))
            $options = array();

        $this->precheckCommon($bucket, $object, $options);
        //method
        if (self::OOS_HTTP_GET !== $method && self::OOS_HTTP_PUT !== $method) {
            throw new OosException("method is invalid");
        }
        $options[self::OOS_BUCKET] = $bucket;
        $options[self::OOS_OBJECT] = $object;
        $options[self::OOS_METHOD] = $method;
        if (!isset($options[self::OOS_CONTENT_TYPE])) {
            $options[self::OOS_CONTENT_TYPE] = '';
        }
        $timeout = time() + (int)$timeout;
        $options[self::OOS_PREAUTH] = $timeout;
        $options[self::OOS_DATE] = $timeout;
        return $this->authSignUrl($options);
    }

    /**
     * validates options. Create a empty array if it's NULL.
     *
     * @param array $options
     * @throws 
     */
    private function precheckOptions(&$options)
    {
        OosUtil::validateOptions($options);
        if (!$options) {
            $options = array();
        }
    }

    /**
     * Validates bucket parameter
     *
     * @param string $bucket
     * @param string $errMsg
     * @throws 
     */
    private function precheckBucket($bucket, $errMsg = 'bucket is not allowed empty')
    {
        OosUtil::throwOssExceptionWithMessageIfEmpty($bucket, $errMsg);
    }

    /**
     * validates object parameter
     *
     * @param string $object
     * @throws 
     */
    private function precheckObject($object)
    {
        OosUtil::throwOssExceptionWithMessageIfEmpty($object, "object name is empty");
    }

    /**
     * 校验option restore
     *
     * @param string $restore
     * @throws 
     */
    private function precheckStorage($storage)
    {
        if (is_string($storage)) {
            switch ($storage) {
                case self::OOS_STORAGE_ARCHIVE:
                    return;
                case self::OOS_STORAGE_IA:
                    return;
                case self::OOS_STORAGE_STANDARD:
                    return;
                default:
                    break;
            }
        }
        throw new OosException('storage name is invalid');
    }

    /**
     * Validates bucket,options parameters and optionally validate object parameter.
     *
     * @param string $bucket
     * @param string $object
     * @param array $options
     * @param bool $isCheckObject
     * @throws
     */
    public function precheckCommon($bucket, $object, &$options, $isCheckObject = true)
    {
        if ($isCheckObject) {
            $this->precheckObject($object);
        }
        $this->precheckOptions($options);
        $this->precheckBucket($bucket);
    }

    /**
     * checks parameters
     *
     * @param array $options
     * @param string $param
     * @param string $funcName
     * @throws 
     */
    private function precheckParam($options, $param, $funcName)
    {
        if (!isset($options[$param])) {
            throw new OosException('The `' . $param . '` options is required in ' . $funcName . '().');
        }
    }

    /**
     * Checks md5
     *
     * @param array $options
     * @return bool|null
     */
    private function isCheckMD5($options)
    {
        return $this->getValue($options, self::OOS_CHECK_MD5, false, true, true);
    }

    /**
     * Gets value of the specified key from the options 
     *
     * @param array $options
     * @param string $key
     * @param string $default
     * @param bool $isCheckEmpty
     * @param bool $isCheckBool
     * @return bool|null
     */
    private function getValue($options, $key, $default = NULL, $isCheckEmpty = false, $isCheckBool = false)
    {
        $value = $default;
        if (isset($options[$key])) {
            if ($isCheckEmpty) {
                if (!empty($options[$key])) {
                    $value = $options[$key];
                }
            } else {
                $value = $options[$key];
            }
            unset($options[$key]);
        }
        if ($isCheckBool) {
            if ($value !== true && $value !== false) {
                $value = false;
            }
        }
        return $value;
    }

    /**
     * Gets mimetype
     *
     * @param string $object
     * @return string
     */
    private function getMimeType($object, $file = null)
    {
        if (!is_null($file)) {
            $type = MimeTypes::getMimetype($file);
            if (!is_null($type)) {
                return $type;
            }
        }

        $type = MimeTypes::getMimetype($object);
        if (!is_null($type)) {
            return $type;
        }

        return self::DEFAULT_CONTENT_TYPE;
    }

    private function getSecond($expires)
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        //return $msectimes = substr($msectime,0,13);
        $secondTime = strval(floatval($msec) +  floatval($expires));
        return $secondTime;
    }


    /**
     * 最终签名是要签名的字符串的HMAC-SHA256哈希值，使用签名密钥作为密钥
     *
     * @param string $signingKey
     * @param string $signingData
     * @return string
     */
    private function getSignedData($signingKey,$signingData)
    {
        // HMAC-SHA256(SigningKey, StringToSign)
        $signedData = hash_hmac('sha256', $signingData, $signingKey, false);
        return $signedData;
    }

    private function getRegionName($options)
    {
        $region = "";
        if(isset($options[self::OOS_REGION])){
            $region = $options[self::OOS_REGION];
        }
        else{
            $region = strtolower($this->hostname);
            if(stripos($region,'iam') >0){
                $region = substr($region,strpos($region,"oos-")+4,
                    (strpos($region,"-iam.") - strpos($region,"oos-")-4));
            }
            else{
                $region = substr($region,strpos($region,"oos-")+4,
                    (strpos($region,".") - strpos($region,"oos-")-4));
            }
        }

        return $region;
    }

    private function getServiceName($options)
    {
        $service = "";
        if(isset($options[self::OOS_SERVICE])){
            $service = $options[self::OOS_SERVICE];
        }
        else{
            $service = "s3";
       }

        return $service;
    }


    private function authSignUrl($options)
    {
        //using v2
        if(!Config::SIGNER_USING_V4)
            return $this->auth($options);

        $timeout = (int)$options[self::OOS_PREAUTH];
        $curTime = time();
        $expires = $timeout - $curTime;

        // Should https or http be used?
        $scheme = $this->useSSL ? 'https://' : 'http://';
        // gets the host name. If the host name is public domain or private domain, form a third level domain by prefixing the bucket name on the domain name.
        $hostname = $this->generateHostname($options[self::OOS_BUCKET]);

        $httpMethod = "GET";
        $region = $this->getRegionName($options);
        $service = $this->getServiceName($options);

        $curDateTime8601 = gmdate('Ymd\THis\Z');

        $curDate = gmdate('Ymd');
        $credentialScope = $curDate . "/" . $region . "/" . $service . "/" . self::OOS_REQUEST_NAME;
        $akCredentialScope = $this->accessKeyId . "/" . $credentialScope;

        //###CanonicalHeaders
        $canonicalHeaders = strtolower(self::OOS_HOST) . ':' . $hostname . "\n";
        $signedHeaders  = strtolower(self::OOS_HOST) ;

        //###CanonicalQueryString
        $signable_query_string_params = array();
        $signable_query_string_params["X-Amz-Date"] = $curDateTime8601;
        $signable_query_string_params["X-Amz-Algorithm"] = "AWS4-HMAC-SHA256";
        $signable_query_string_params["X-Amz-SignedHeaders"] = $signedHeaders;
        $signable_query_string_params["X-Amz-Credential"] = $akCredentialScope;
        $signable_query_string_params["X-Amz-Expires"] = $expires;

        if(isset($options[OosClient::OOS_LIMITRATE]))
            $signable_query_string_params[OosClient::OOS_LIMITRATE] = $options[OosClient::OOS_LIMITRATE];

        $canonicalQueryString = OosUtil::toQueryString($signable_query_string_params,"strcmp");

        //###CanonicalURI【不包含acl等子资源，如果有子资源仅仅包含问号】
        $canonicalURI = $this->generateSignableResourceV4($options);

        //###$payload
        $payload = self::OOS_CONTENT_NO_PAYLOAD;

        $canonicalRequest = $httpMethod . "\n"
            . $canonicalURI . "\n"
            . $canonicalQueryString . "\n"
            . $canonicalHeaders . "\n"
            . $signedHeaders . "\n"
            . $payload;
        //print("\n\n===PHP AWS4Signer Calculated canonicalString: ======\n" . $canonicalRequest . "\n=============");

        $canonicalRequestHash = hash("sha256",$canonicalRequest,false);
        //print("\n\n===PHP AWS4Signer Calculated canonicalRequestHash: ==\n" . $canonicalRequestHash . "\n=======");

        $string_to_sign = self::OOS_HMAC_SHA256 . "\n"
            . $curDateTime8601 . "\n" . $credentialScope . "\n" . $canonicalRequestHash;
        //print("\n\n===PHP AWS4Signer Calculated string_to_sign: ==\n" . $string_to_sign . "\n=======\n");
        $service = $this->getServiceName($options);
        $signatureResult = $this->computeSignature($curDate,$region,$service,$string_to_sign);

        $signed_url = $scheme . $hostname  . "/" . $options[self::OOS_OBJECT] . "?"
            . "X-Amz-Date=" . $curDateTime8601
            . "&X-Amz-Algorithm=AWS4-HMAC-SHA256"
            . "&X-Amz-Signature=" . $signatureResult
            . "&X-Amz-SignedHeaders=" . $signedHeaders
            . "&X-Amz-Credential=" . $akCredentialScope
            . "&X-Amz-Expires=" . $expires
        ;
        if(isset($options[OosClient::OOS_LIMITRATE]))
            $signed_url .= "&" . OosClient::OOS_LIMITRATE . "=" . $options[OosClient::OOS_LIMITRATE];
        return $signed_url;
    }

    private function computeSignature($curDate,$region,$service,$string_to_sign){
        $kSecret = mb_convert_encoding(("AWS4" . $this->accessKeySecret), "UTF-8");
        $kDate = hash_hmac('sha256',$curDate, $kSecret, true);

        $kRegion = hash_hmac('sha256', $region, $kDate, true);

        $kService = hash_hmac('sha256', $service, $kRegion, true);

        $kSigning =  hash_hmac('sha256', self::OOS_REQUEST_NAME, $kService, true);

        $signatureResult = hash_hmac('sha256', $string_to_sign, $kSigning, false);

        return $signatureResult;
    }
    /**
     * Validates and executes the request according to OOS API protocol.
     *
     * @param array $options
     * @return ResponseCore
     * @throws
     * @throws
     */
    private function auth($options)
    {
        //是否使用V4签名
        if(Config::SIGNER_USING_V4) {
            return $this->authV4($options);
        }
        else{
            return $this->authV2($options);
        }
    }

    /**
     * Validates and executes the request according to OOS API protocol V4.
     *
     * @param array $options
     * @return ResponseCore
     * @throws
     * @throws
     */
    private function authV4($options)
    {
        OosUtil::validateOptions($options);
        if(isset($options[self::OOS_BUCKET]) && strlen($options[self::OOS_BUCKET])>0){
            //Validates bucket, not required for list_bucket
            $this->authPrecheckBucket($options);
            //Validates object
            $this->authPrecheckObject($options);
            //object name encoding must be UTF-8
            $this->authPrecheckObjectEncoding($options);
            //Validates ACL
            $this->authPrecheckAcl($options);
        }
        else{
            $options[self::OOS_BUCKET] = "";
        }

        // Should https or http be used?
        $scheme = $this->useSSL ? 'https://' : 'http://';
        // gets the host name. If the host name is public domain or private domain, form a third level domain by prefixing the bucket name on the domain name.
        $hostname = $this->generateHostname($options[self::OOS_BUCKET]);

        $userAgent = $this->generateUserAgent();

        $payload = self::OOS_CONTENT_NO_PAYLOAD;

        if(Config::SIGNER_USING_PAYLOAD){
            if(isset($options[self::OOS_CONTENT_NO_PAYLOAD])){
                $payload = self::OOS_CONTENT_NO_PAYLOAD;
            }
            else if(isset($options[self::OOS_FILE_UPLOAD]) || isset($options[self::OOS_SEEK_TO]))
                $payload = self::OOS_CONTENT_NO_PAYLOAD;
            else if(isset($options[self::OOS_CONTENT]) && strlen($options[self::OOS_CONTENT]) >0){
                $payload = $this->SHA256Hex($options[self::OOS_CONTENT]);
            }
            else{
                $payload = self::EMPTY_BODY_SHA256;
            }
        }

        $curDateTime8601 = gmdate('Ymd\THis\Z');
        $headers = $this->generateHeadersV4($options, $hostname,$payload,$userAgent,$curDateTime8601);

        //签名的查询参数
        $signable_query_string_params = $this->generateSignableQueryStringParamV4($options);
        $signable_query_string = OosUtil::toQueryString($signable_query_string_params);
        //###CanonicalQueryString
        $canonicalQueryString = $signable_query_string;

        $resource_uri = $this->generateResourceUri($options);
        $queryStringNoSubResource = OosUtil::toQueryString($this->generateQueryStringNoSubResourceParamV4($options));

        if (isset($options[self::OOS_SUB_RESOURCE])) {
            if(strlen($queryStringNoSubResource)>0){
                $rightPartURL = $resource_uri . '&' . $queryStringNoSubResource;
            }
            else{
                $rightPartURL = $resource_uri;
            }
        }
        else{
            if(strlen($queryStringNoSubResource)>0){
                $rightPartURL = $resource_uri . '?' . $queryStringNoSubResource;
            }
            else{
                $rightPartURL = $resource_uri;
            }
        }

        if(strlen($rightPartURL)<1)
            $rightPartURL = "/";

        $this->requestUrl = $scheme . $hostname . $rightPartURL;
        print("\n===========this->requestUrl:\n" . $this->requestUrl . "\n");

        //Creates the request
        $request = new RequestCore($this->requestUrl, $this->requestProxy);
        $request->set_useragent($userAgent);

        // Streaming uploads
        if (isset($options[self::OOS_FILE_UPLOAD])) {
            if (is_resource($options[self::OOS_FILE_UPLOAD])) {
                $length = null;

                if (isset($options[self::OOS_CONTENT_LENGTH])) {
                    $length = $options[self::OOS_CONTENT_LENGTH];
                } elseif (isset($options[self::OOS_SEEK_TO])) {
                    $stats = fstat($options[self::OOS_FILE_UPLOAD]);
                    if ($stats && $stats[self::OOS_SIZE] >= 0) {
                        $length = $stats[self::OOS_SIZE] - (integer)$options[self::OOS_SEEK_TO];
                    }
                }
                $request->set_read_stream($options[self::OOS_FILE_UPLOAD], $length);
            } else {
                $request->set_read_file($options[self::OOS_FILE_UPLOAD]);
                $length = $request->read_stream_size;
                if (isset($options[self::OOS_CONTENT_LENGTH])) {
                    $length = $options[self::OOS_CONTENT_LENGTH];
                } elseif (isset($options[self::OOS_SEEK_TO]) && isset($length)) {
                    $length -= (integer)$options[self::OOS_SEEK_TO];
                }
                $request->set_read_stream_size($length);
            }
        }
        if (isset($options[self::OOS_SEEK_TO])) {
            $request->set_seek_position((integer)$options[self::OOS_SEEK_TO]);
        }
        if (isset($options[self::OOS_FILE_DOWNLOAD])) {
            if (is_resource($options[self::OOS_FILE_DOWNLOAD])) {
                $request->set_write_stream($options[self::OOS_FILE_DOWNLOAD]);
            } else {
                $request->set_write_file($options[self::OOS_FILE_DOWNLOAD]);
            }
        }

        if (isset($options[self::OOS_CONTENT])) {
            $request->set_body($options[self::OOS_CONTENT]);
        }
        $string_to_sign = '';

        //1 创建规范请求
        //CanonicalRequest =
        //  HttpMethod + '\n' +
        //  CanonicalURI + '\n' +
        //  CanonicalQueryString + '\n' +
        //  CanonicalHeaders + '\n' +
        //  SignedHeaders + '\n' +
        //  HexEncode(Hash(RequestPayload))

        $httpMethod = "";
        $canonicalHeaders = "";
        $signedHeaders = "";

        //###Http method
        if (isset($options[self::OOS_METHOD])) {
            $request->set_method($options[self::OOS_METHOD]);
            $httpMethod = $options[self::OOS_METHOD];
        }

        //###CanonicalHeaders
        //content-type;host;user-agent;x-amz-content-sha256;x-amz-date
        uksort($headers, 'strnatcasecmp');

        foreach ($headers as $header_key => $header_value) {
            $header_value = str_replace(array("\r", "\n"), '', $header_value);
            if ($header_value !== '' || $header_key === self::OOS_ACCEPT_ENCODING) {
                $request->add_header($header_key, $header_value);
            }

            if (
                substr(strtolower($header_key), 0, 6) === self::OOS_DEFAULT_PREFIX ||
                strtolower($header_key) === 'host'
                || strtolower($header_key) === 'content-type'
                || strtolower($header_key) === 'content-length'
            ) {
                $canonicalHeaders .= strtolower($header_key) . ':' . $header_value . "\n";
                $signedHeaders  .= strtolower($header_key) . ';';
            }
        }

        if(strlen($signedHeaders)>0){
            $signedHeaders = substr($signedHeaders,0,strlen($signedHeaders)-1);
        }

        //###CanonicalURI【不包含acl等子资源，如果有子资源仅仅包含问号】
        $canonicalURI = $this->generateSignableResourceV4($options);

        $region = $this->getRegionName($options);
        $service = $this->getServiceName($options);

        $canonicalRequest = $httpMethod . "\n"
            . $canonicalURI . "\n"
            . $canonicalQueryString . "\n"
            . $canonicalHeaders . "\n"
            . $signedHeaders . "\n"
            . $payload;

        print("\n\n===PHP AWS4Signer Calculated canonicalString: ======\n" . $canonicalRequest . "\n=============\n");
        $canonicalRequestHash = hash("sha256",$canonicalRequest,false);
        print("\n\n=======canonicalRequestHash:=========\n" . $canonicalRequestHash . "\n=======================\n");

        $curDate = gmdate('Ymd');
        $credentialScope = $curDate . "/" . $region . "/" . $service . "/" . self::OOS_REQUEST_NAME;
        $akCredentialScope = $this->accessKeyId . "/" . $credentialScope;

        $string_to_sign = self::OOS_HMAC_SHA256 . "\n"
            . $curDateTime8601 . "\n" . $credentialScope . "\n" . $canonicalRequestHash;

        print("\n\n=======string_to_sign_ordered:=========\n" . $string_to_sign . "\n=======================\n");
        $signatureResult = $this->computeSignature($curDate,$region,$service,$string_to_sign);
        print("\n\n=======php signatureResult:=========\n" . $signatureResult . "\n=======================\n");

        //将签名信息添加到Authorization标头的伪代码如下
        //Authorization: algorithm Credential=ak/credential_scope, SignedHeaders=SignedHeaders, Signature=signature
        //Authorization: AWS4-HMAC-SHA256 Credential=2811928c2878a54ba36e3/20190215/hazz/s3/aws4_request,
        // SignedHeaders=content-type;host;user-agent;x-amz-content-sha256;x-amz-date, Signature=0c803dd7d3fc578ab841496e395b928134c31cb01acc4ebb26879c6c888214fe
        $authorizationHeaderContent = self::OOS_HMAC_SHA256 . " " . "Credential=" .$akCredentialScope
            . ", " . "SignedHeaders=" . $signedHeaders
            . ", " . "Signature=" . $signatureResult
        ;
        $request->add_header('Authorization', $authorizationHeaderContent);

        if ($this->timeout !== 0) {
            $request->timeout = $this->timeout;
        }
        if ($this->connectTimeout !== 0) {
            $request->connect_timeout = $this->connectTimeout;
        }

        try {
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            throw(new OosException('RequestCoreException: ' . $e->getMessage()));
        }

        $response_header = $request->get_response_header();
        $response_header['oss-request-url'] = $this->requestUrl;
        $response_header['oss-redirects'] = $this->redirects;
        $response_header['oss-stringtosign'] = $string_to_sign;
        $response_header['oss-requestheaders'] = $request->request_headers;

        $data = new ResponseCore($response_header, $request->get_response_body(), $request->get_response_code());
        //retry if OOS Internal Error
        if ((integer)$request->get_response_code() === 500) {
            if ($this->redirects <= $this->maxRetries) {
                //Sets the sleep time betwen each retry.
                $delay = (integer)(pow(4, $this->redirects) * 100000);
                usleep($delay);
                $this->redirects++;
                $data = $this->auth($options);
            }
        }

        $this->redirects = 0;
        return $data;
    }

    /**
     * Validates and executes the request according to OOS API protocol.
     *
     * @param array $options
     * @return ResponseCore
     * @throws 
     * @throws
     */
    private function authV2($options)
    {
        OosUtil::validateOptions($options);

        if(isset($options[self::OOS_BUCKET]) && strlen($options[self::OOS_BUCKET]) >0){
            //Validates bucket, not required for list_bucket
            $this->authPrecheckBucket($options);
            //Validates object
            $this->authPrecheckObject($options);
            //object name encoding must be UTF-8
            $this->authPrecheckObjectEncoding($options);
            //Validates ACL
            $this->authPrecheckAcl($options);
        }
        else{
            $options[self::OOS_BUCKET] = "";
        }

        // Should https or http be used?
        $scheme = $this->useSSL ? 'https://' : 'http://';
        // gets the host name. If the host name is public domain or private domain,
        // form a third level domain by prefixing the bucket name on the domain name.
        $hostname = $this->generateHostname($options[self::OOS_BUCKET]);
        $string_to_sign = '';
        $headers = $this->generateHeaders($options, $hostname);
        $signable_query_string_params = $this->generateSignableQueryStringParam($options);
        $signable_query_string = OosUtil::toQueryString($signable_query_string_params);
        $resource_uri = $this->generateResourceUri($options);

        //Generates the URL (add query parameters)
        $conjunction = '?';
        $non_signable_resource = '';
        if (isset($options[self::OOS_SUB_RESOURCE])) {
            $conjunction = '&';
        }
        if ($signable_query_string !== '') {
            $signable_query_string = $conjunction . $signable_query_string;
            $conjunction = '&';
        }
        $query_string = $this->generateQueryString($options);
        if ($query_string !== '') {
            $non_signable_resource .= $conjunction . $query_string;
            $conjunction = '&';
        }
        $this->requestUrl = $scheme . $hostname . $resource_uri . $signable_query_string . $non_signable_resource;

        //Creates the request
        $request = new RequestCore($this->requestUrl, $this->requestProxy);
        $request->set_useragent($this->generateUserAgent());
        // Streaming uploads
        if (isset($options[self::OOS_FILE_UPLOAD])) {
            if (is_resource($options[self::OOS_FILE_UPLOAD])) {
                $length = null;

                if (isset($options[self::OOS_CONTENT_LENGTH])) {
                    $length = $options[self::OOS_CONTENT_LENGTH];
                } elseif (isset($options[self::OOS_SEEK_TO])) {
                    $stats = fstat($options[self::OOS_FILE_UPLOAD]);
                    if ($stats && $stats[self::OOS_SIZE] >= 0) {
                        $length = $stats[self::OOS_SIZE] - (integer)$options[self::OOS_SEEK_TO];
                    }
                }
                $request->set_read_stream($options[self::OOS_FILE_UPLOAD], $length);
            } else {
                $request->set_read_file($options[self::OOS_FILE_UPLOAD]);
                $length = $request->read_stream_size;
                if (isset($options[self::OOS_CONTENT_LENGTH])) {
                    $length = $options[self::OOS_CONTENT_LENGTH];
                } elseif (isset($options[self::OOS_SEEK_TO]) && isset($length)) {
                    $length -= (integer)$options[self::OOS_SEEK_TO];
                }
                $request->set_read_stream_size($length);
            }
        }
        if (isset($options[self::OOS_SEEK_TO])) {
            $request->set_seek_position((integer)$options[self::OOS_SEEK_TO]);
        }
        if (isset($options[self::OOS_FILE_DOWNLOAD])) {
            if (is_resource($options[self::OOS_FILE_DOWNLOAD])) {
                $request->set_write_stream($options[self::OOS_FILE_DOWNLOAD]);
            } else {
                $request->set_write_file($options[self::OOS_FILE_DOWNLOAD]);
            }
        }

        if (isset($options[self::OOS_METHOD])) {
            $request->set_method($options[self::OOS_METHOD]);
            $string_to_sign .= $options[self::OOS_METHOD] . "\n";
        }

        if (isset($options[self::OOS_CONTENT])) {
            $request->set_body($options[self::OOS_CONTENT]);
            if ($headers[self::OOS_CONTENT_TYPE] === 'application/x-www-form-urlencoded') {
                $headers[self::OOS_CONTENT_TYPE] = 'application/octet-stream';
            }
            $headers[self::OOS_CONTENT_LENGTH] = strlen($options[self::OOS_CONTENT]);
            $md5Value = md5($options[self::OOS_CONTENT], true);
            $headers[self::OOS_CONTENT_MD5] = base64_encode($md5Value);
        }

        if (isset($options[self::OOS_CALLBACK])) {
            $headers[self::OOS_CALLBACK] = base64_encode($options[self::OOS_CALLBACK]);
        }
        if (isset($options[self::OOS_CALLBACK_VAR])) {
            $headers[self::OOS_CALLBACK_VAR] = base64_encode($options[self::OOS_CALLBACK_VAR]);
        }

        if (!isset($headers[self::OOS_ACCEPT_ENCODING])) {
            $headers[self::OOS_ACCEPT_ENCODING] = '';
        }

        uksort($headers, 'strnatcasecmp');

        foreach ($headers as $header_key => $header_value) {
            $header_value = str_replace(array("\r", "\n"), '', $header_value);
            if ($header_value !== '' || $header_key === self::OOS_ACCEPT_ENCODING) {
                $request->add_header($header_key, $header_value);
            }
            if (
                strtolower($header_key) === 'content-md5' ||
                strtolower($header_key) === 'content-type' ||
                strtolower($header_key) === 'date'
            ) {
                $string_to_sign .= $header_value . "\n";
            } elseif (substr(strtolower($header_key), 0, 6) === self::OOS_DEFAULT_PREFIX) {
                $string_to_sign .= strtolower($header_key) . ':' . $header_value . "\n";
            }
        }

        // Generates the signable_resource
        $signable_resource = $this->generateSignableResource($options);

        $string_to_sign .= rawurldecode($signable_resource) . urldecode($signable_query_string);

        // Sort the strings to be signed.
        $string_to_sign_ordered = $this->stringToSignSorted($string_to_sign);

        print("\nphp S3Signer canonicalString:================\n" . $string_to_sign_ordered . "\n=======================\n");

        $signature = base64_encode(hash_hmac('sha1', $string_to_sign_ordered, $this->accessKeySecret, true));
        $request->add_header('Authorization', 'AWS ' . $this->accessKeyId . ':' . $signature);

        if (isset($options[self::OOS_PREAUTH]) && (integer)$options[self::OOS_PREAUTH] > 0) {
            //http://testphpa.oos-cn.ctyunapi.cn/object.php?
            //Signature=Q2LqK8VcV6wTxZYbek1mfUZcPvA%3D
            //&AWSAccessKeyId=3fb3263981f4d1500d41
            //&Expires=1563379200
            $signed_url = $this->requestUrl . $conjunction
                . self::OOS_URL_ACCESS_KEY_ID . '=' . rawurlencode($this->accessKeyId)
                . '&' . self::OOS_URL_EXPIRES . '=' . $options[self::OOS_PREAUTH]
                . '&' . self::OOS_URL_SIGNATURE . '=' . rawurlencode($signature);
            if(isset($options[OosClient::OOS_LIMITRATE]))
                $signed_url .= "&" . OosClient::OOS_LIMITRATE . "=" . $options[OosClient::OOS_LIMITRATE];

            return $signed_url;
        }

        if ($this->timeout !== 0) {
            $request->timeout = $this->timeout;
        }
        if ($this->connectTimeout !== 0) {
            $request->connect_timeout = $this->connectTimeout;
        }

        try {
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            throw(new OosException('RequestCoreException: ' . $e->getMessage()));
        }
        $response_header = $request->get_response_header();
        $response_header['oss-request-url'] = $this->requestUrl;
        $response_header['oss-redirects'] = $this->redirects;
        $response_header['oss-stringtosign'] = $string_to_sign;
        $response_header['oss-requestheaders'] = $request->request_headers;

        $data = new ResponseCore($response_header, $request->get_response_body(), $request->get_response_code());
        //retry if OOS Internal Error
        if ((integer)$request->get_response_code() === 500) {
            if ($this->redirects <= $this->maxRetries) {
                //Sets the sleep time betwen each retry.
                $delay = (integer)(pow(4, $this->redirects) * 100000);
                usleep($delay);
                $this->redirects++;
                $data = $this->auth($options);
            }
        }
        
        $this->redirects = 0;
        return $data;
    }


    /**
     * Sets the max retry count
     *
     * @param int $maxRetries
     * @return void
     */
    public function setMaxTries($maxRetries = 3)
    {
        $this->maxRetries = $maxRetries;
    }

    /**
     * Gets the max retry count
     *
     * @return int
     */
    public function getMaxRetries()
    {
        return $this->maxRetries;
    }

    /**
     * Enaable/disable STS in the URL. This is to determine the $sts value passed from constructor take effect or not.
     *
     * @param boolean $enable
     */
    public function setSignStsInUrl($enable)
    {
        $this->enableStsInUrl = $enable;
    }

    /**
     * @return boolean
     */
    public function isUseSSL()
    {
        return $this->useSSL;
    }

    /**
     * @param boolean $useSSL
     */
    public function setUseSSL($useSSL)
    {
        $this->useSSL = $useSSL;
    }

    /**
     * Validates bucket name--throw OosException if it's invalid
     *
     * @param $options
     * @throws 
     */
    private function authPrecheckBucket($options)
    {
        if (!(('/' == $options[self::OOS_OBJECT]) && ('' == $options[self::OOS_BUCKET]) && ('GET' == $options[self::OOS_METHOD])) && !OosUtil::validateBucket($options[self::OOS_BUCKET])) {
            throw new OosException('"' . $options[self::OOS_BUCKET] . '"' . 'bucket name is invalid');
        }
    }

    /**
     *
     * Validates the object name--throw OosException if it's invalid.
     *
     * @param $options
     * @throws 
     */
    private function authPrecheckObject($options)
    {
        if (isset($options[self::OOS_OBJECT]) && $options[self::OOS_OBJECT] === '/') {
            return;
        }

        if (isset($options[self::OOS_OBJECT]) && !OosUtil::validateObject($options[self::OOS_OBJECT])) {
            throw new OosException('"' . $options[self::OOS_OBJECT] . '"' . ' object name is invalid');
        }
    }

    /**
     * Checks the object's encoding. Convert it to UTF8 if it's in GBK or GB2312
     *
     * @param mixed $options parameter
     */
    private function authPrecheckObjectEncoding(&$options)
    {
        $tmp_object = $options[self::OOS_OBJECT];
        try {
            if (OosUtil::isGb2312($options[self::OOS_OBJECT])) {
                $options[self::OOS_OBJECT] = iconv('GB2312', "UTF-8//IGNORE", $options[self::OOS_OBJECT]);
            } elseif (OosUtil::checkChar($options[self::OOS_OBJECT], true)) {
                $options[self::OOS_OBJECT] = iconv('GBK', "UTF-8//IGNORE", $options[self::OOS_OBJECT]);
            }
        } catch (\Exception $e) {
            try {
                $tmp_object = iconv(mb_detect_encoding($tmp_object), "UTF-8", $tmp_object);
            } catch (\Exception $e) {
            }
        }
        $options[self::OOS_OBJECT] = $tmp_object;
    }

    /**
     * Checks if the ACL is one of the 3 predefined one. Throw OSSException if not.
     *
     * @param $options
     * @throws 
     */
    private function authPrecheckAcl($options)
    {
        if (isset($options[self::OOS_HEADERS][self::OOS_ACL]) && !empty($options[self::OOS_HEADERS][self::OOS_ACL])) {
            if (!in_array(strtolower($options[self::OOS_HEADERS][self::OOS_ACL]), self::$OOS_ACL_TYPES)) {
                throw new OosException($options[self::OOS_HEADERS][self::OOS_ACL] . ':' . 'acl is invalid(private,public-read,public-read-write)');
            }
        }
    }

    /**
     * Gets the host name for the current request.
     * It could be either a third level domain (prefixed by bucket name) or second level domain if it's CName or IP
     *
     * @param $bucket
     * @return string The host name without the protocol scheem (e.g. https://)
     */
    private function generateHostname($bucket)
    {
        $bucket1 = "";
        if(isset($bucket)){
            $bucket1 = $bucket;
        }
        if ($this->hostType === self::OOS_HOST_TYPE_IP) {
            $hostname = $this->hostname;
        } elseif ($this->hostType === self::OOS_HOST_TYPE_CNAME) {
            $hostname = $this->hostname;
        } else {
            // Private domain or public domain
            $hostname = ($bucket1 == '') ? $this->hostname : ($bucket1 . '.') . $this->hostname;
        }
        return $hostname;
    }

    /**
 * Gets the resource Uri in the current request
 *
 * @param $options
 * @return string return the resource uri.
 */
    private function generateResourceUri($options)
    {
        $resource_uri = "";

        // resource_uri + bucket
        if (isset($options[self::OOS_BUCKET]) && '' !== $options[self::OOS_BUCKET]) {
            if ($this->hostType === self::OOS_HOST_TYPE_IP) {
                $resource_uri = '/' . $options[self::OOS_BUCKET];
            }
        }

        // resource_uri + object
        if (isset($options[self::OOS_OBJECT]) && '/' !== $options[self::OOS_OBJECT]) {
            $resource_uri .= '/' . str_replace(array('%2F', '%25'), array('/', '%'), rawurlencode($options[self::OOS_OBJECT]));
        }

        // resource_uri + sub_resource
        $conjunction = '?';
        if (isset($options[self::OOS_SUB_RESOURCE])) {
            $resource_uri .= $conjunction . $options[self::OOS_SUB_RESOURCE];
        }

        if(strlen($resource_uri)<1)
            $resource_uri="/";

        return $resource_uri;
    }
    /**
     * Generates the signalbe query string parameters in array type
     *
     * @param array $options
     * @return array
     */
    private function generateSignableQueryStringParamV4($options)
    {
        $queryStringParams = array();
        $signableQueryStringParams = array();
        if (isset($options[self::OOS_SUB_RESOURCE])) {
            $queryStringParams[$options[self::OOS_SUB_RESOURCE]] = "";
        }
        if (isset($options[self::OOS_QUERY_STRING])) {
            $queryStringParams = array_merge($queryStringParams, $options[self::OOS_QUERY_STRING]);
        }

        $mustSignableQueryStringParams = $this->generateSignableQueryStringParam($options);

        $signableQueryStringParams = array_merge($signableQueryStringParams, $mustSignableQueryStringParams);

        foreach ($queryStringParams as $param_key => $param_value) {
            if(!isset($signableQueryStringParams[$param_key])){
                $signableQueryStringParams[$param_key] = $param_value;
            }
        }

        return $signableQueryStringParams;
    }
    /**
     * Generates the signalbe query string parameters in array type
     *
     * @param array $options
     * @return array
     */
    private function generateQueryStringNoSubResourceParamV4($options)
    {
        $queryStringParams = array();
        $queryStringParamsNoSubResource = array();

        if (isset($options[self::OOS_QUERY_STRING])) {
            $queryStringParams = array_merge($queryStringParamsNoSubResource, $options[self::OOS_QUERY_STRING]);
        }

        $mustSignableQueryStringParams = $this->generateSignableQueryStringParam($options);

        $queryStringParamsNoSubResource = array_merge($queryStringParamsNoSubResource, $mustSignableQueryStringParams);

        foreach ($queryStringParams as $param_key => $param_value) {
            if(!isset($queryStringParamsNoSubResource[$param_key])){
                $queryStringParamsNoSubResource[$param_key] = $param_value;
            }
        }

        return $queryStringParamsNoSubResource;
    }
    /**
     * Generates the signalbe query string parameters in array type
     *
     * @param array $options
     * @return array
     */
    private function generateSignableQueryStringParam($options)
    {
        $signableQueryStringParams = array();
        $signableList = array(
            "acl",
            "torrent",
            "logging",
            "location",
            "policy",
            "requestPayment",
            "versioning",
            "versions",
            "versionId",
            "notification",
            "uploadId",
            "uploads",
            "partNumber",
            "website",
            "delete",
            "lifecycle",
            "tagging",
            "cors",
            "restore",
            "response-content-type",
            "response-content-language",
            "response-expires",
            "response-cache-control",
            "response-content-disposition",
            "response-content-encoding"
        );

        foreach ($signableList as $item) {
            if (isset($options[$item])) {
                $signableQueryStringParams[$item] = $options[$item];
            }
        }

        if ($this->enableStsInUrl && (!is_null($this->securityToken))) {
            $signableQueryStringParams["security-token"] = $this->securityToken;
        }

        return $signableQueryStringParams;
    }

    /**
     *  Generates the resource uri for signing
     *
     * @param mixed $options
     * @return string
     */
    private function generateSignableResource($options)
    {
        $signableResource = "";
        $signableResource .= '/';
        if (isset($options[self::OOS_BUCKET]) && '' !== $options[self::OOS_BUCKET]) {
            $signableResource .= $options[self::OOS_BUCKET];
            // if there's no object in options, adding a '/' if the host type is not IP.\
            if ($options[self::OOS_OBJECT] == '/') {
                if ($this->hostType !== self::OOS_HOST_TYPE_IP) {
                    $signableResource .= "/";
                }
            }
        }
        //signable_resource + object
        if (isset($options[self::OOS_OBJECT]) && '/' !== $options[self::OOS_OBJECT]) {
            $signableResource .= '/' . str_replace(array('%2F', '%25'), array('/', '%'), rawurlencode($options[self::OOS_OBJECT]));
        }

        //@todo accelerate ????
        if (isset($options[self::OOS_SUB_RESOURCE])) {
            if((string)$options[self::OOS_SUB_RESOURCE] !== "accelerate" && (string)$options[self::OOS_SUB_RESOURCE] !== "regions" )
                $signableResource .= '?' . $options[self::OOS_SUB_RESOURCE];
        }

        return $signableResource;
    }

    /**
     *  Generates the resource uri for signing
     *
     * @param mixed $options
     * @return string
     */
    private function generateSignableResourceV4($options)
    {
        $signableResource = '/';

        //signable_resource + object
        if (isset($options[self::OOS_OBJECT]) && '/' !== $options[self::OOS_OBJECT]) {
            $signableResource = '/' . str_replace(array('%2F', '%25'), array('/', '%'), rawurlencode($options[self::OOS_OBJECT]));
        }

        return $signableResource;
    }

    /**
     * generates query string
     *
     * @param mixed $options
     * @return string
     */
    private function generateQueryString($options)
    {
        //query parameters
        $queryStringParams = array();
        if (isset($options[self::OOS_QUERY_STRING])) {
            $queryStringParams = array_merge($queryStringParams, $options[self::OOS_QUERY_STRING]);
        }
        return OosUtil::toQueryString($queryStringParams);
    }

    private function stringToSignSorted($string_to_sign)
    {
        $queryStringSorted = '';
        $explodeResult = explode('?', $string_to_sign);
        $index = count($explodeResult);
        if ($index === 1)
            return $string_to_sign;

        $queryStringParams = explode('&', $explodeResult[$index - 1]);
        sort($queryStringParams);

        foreach($queryStringParams as $params)
        {
             $queryStringSorted .= $params . '&';    
        }

        $queryStringSorted = substr($queryStringSorted, 0, -1);

        return $explodeResult[0] . '?' . $queryStringSorted;
    }

    /**
     * Initialize v4 headers
     *
     * @param mixed $options
     * @param string $hostname hostname
     * @param string $payload payload
     * @param string $userAgent userAgent
     * @param string $curDateTime curDateTime
     * @return array
     * @throws
     */
    private function generateHeadersV4($options, $hostname,$payload,$userAgent,$curDateTime)
    {
        //GET / HTTP/1.1
        //11 Host: oos-hazz.ctyunapi.cn
        //22 x-amz-content-sha256: UNSIGNED-PAYLOAD
        //3 Authorization: AWS4-HMAC-SHA256 Credential=28a928c2878a54ba36e3/20190215/hazz/s3/aws4_request, SignedHeaders=content-type;host;user-agent;x-amz-content-sha256;x-amz-date, Signature=0c803dd7d3fc578ab841496e395b928134c31cb01acc4ebb26879c6c888214fe
        //44 X-Amz-Date: 20190215T075056Z
        //55 User-Agent: aws-sdk-java/6.1.0 Windows_10/10.0 Java_HotSpot(TM)_64-Bit_Server_VM/25.192-b12
        //66 Content-Type: application/x-www-form-urlencoded; charset=utf-8
        //Connection: Keep-Alive
        //$headers["user-agent"] = "aws-sdk-java/6.1.0 Windows_10/10.0 Java_HotSpot(TM)_64-Bit_Server_VM/25.192-b12";
        //$headers["x-amz-date"] = "20190724T023416Z";
        $headers = array(
            self::OOS_HOST => $hostname,
            self::OOS_CONTENT_SHA256 => $payload,
            self::OOS_AUTHORIZATION => '',
            self::OOS_HEAD_DATE => $curDateTime,
            self::OOS_USER_AGENT => $userAgent,
            self::OOS_CONTENT_LENGTH => isset($options[self::OOS_CONTENT_LENGTH]) ? $options[self::OOS_CONTENT_LENGTH] : 0,
            self::OOS_CONTENT_TYPE => 'application/x-www-form-urlencoded; charset=utf-8'
        );

        //Merge HTTP headers
        if (isset($options[self::OOS_HEADERS])) {
            $newHeaders = $this->checkHeaders($options[self::OOS_HEADERS],$options);
            $headers = array_merge($headers, $newHeaders);
        }

        //Add stsSecurityToken
        if ((!is_null($this->securityToken)) && (!$this->enableStsInUrl)) {
            $headers[self::OOS_SECURITY_TOKEN] = $this->securityToken;
        }

        //x-ctyun-data-location
        if(isset($options[self::OOS_CTYUN_DATA_LOCATION])){
            $headers[self::OOS_CTYUN_DATA_LOCATION] = $options[self::OOS_CTYUN_DATA_LOCATION];
        }
        if(isset($options[self::OOS_CONTENT_LENGTH])){
            $headers[self::OOS_CONTENT_LENGTH] = $options[self::OOS_CONTENT_LENGTH];
        }

        if(isset($options[self::OOS_CONTENT])){
            $headers[self::OOS_CONTENT_LENGTH] = strlen($options[self::OOS_CONTENT]);
            $md5Value = md5($options[self::OOS_CONTENT], true);
            $headers[self::OOS_CONTENT_MD5] = base64_encode($md5Value);
        }

        if(isset($options[self::OOS_CONTENT_TYPE])){
            $headers[self::OOS_CONTENT_TYPE] = $options[self::OOS_CONTENT_TYPE];
        }

        return $headers;
    }

    /**
     * Initialize headers
     *
     * @param mixed $options
     * @param string $hostname hostname
     * @return array
     * @throws
     */
    private function generateHeaders($options, $hostname)
    {
        $headers = array(
            self::OOS_CONTENT_MD5 => '',
            self::OOS_CONTENT_TYPE => isset($options[self::OOS_CONTENT_TYPE]) ? $options[self::OOS_CONTENT_TYPE] : self::DEFAULT_CONTENT_TYPE,
            self::OOS_DATE => isset($options[self::OOS_DATE]) ? $options[self::OOS_DATE] : gmdate('D, d M Y H:i:s \G\M\T'),
            self::OOS_HOST => $hostname,
        );

        if(isset($options[OosClient::OOS_LIMITRATE])){
            $headers[OosClient::OOS_LIMITRATE] = $options[OosClient::OOS_LIMITRATE];
        }

        //Merge HTTP headers
        if (isset($options[self::OOS_HEADERS])) {
            $newHeaders = $this->checkHeaders($options[self::OOS_HEADERS],$options);
            $headers = array_merge($headers, $newHeaders);
        }

        if (isset($options[self::OOS_CONTENT_MD5])) {
            $headers[self::OOS_CONTENT_MD5] = $options[self::OOS_CONTENT_MD5];
        }

        //Add stsSecurityToken
        if ((!is_null($this->securityToken)) && (!$this->enableStsInUrl)) {
            $headers[self::OOS_SECURITY_TOKEN] = $this->securityToken;
        }
        //x-ctyun-data-location
        if(isset($options[self::OOS_CTYUN_DATA_LOCATION])){
            $headers[self::OOS_CTYUN_DATA_LOCATION] = $options[self::OOS_CTYUN_DATA_LOCATION];
        }
        return $headers;
    }

    //检验用户提交的头部设定
    private function checkHeaders($headers,&$options)
    {
        $newHeaders = array();
        foreach ($headers as $header_key => $header_value) {
            $header_value = str_replace(array("\r", "\n"), '', $header_value);
            $newHeaders[$header_key] = $header_value;
            if ( strtolower($header_key)  ==   strtolower(self::OOS_CONTENT_MD5)) {
                if(!isset($options[self::OOS_CONTENT])){
                    throw new OosException("Md5 is set but Content is null.");
                }
                $md5Value = base64_encode(md5($options[self::OOS_CONTENT], true));
                if($md5Value != $header_value){
                    throw new OosException("The content's md5 is not correct.");
                }
                unset($newHeaders[$header_value]);
            }
            if ($header_key === self::OOS_CONTENT_TYPE) {
                unset($newHeaders[$header_value]);
                $options[self::OOS_CONTENT_TYPE] = $header_value;
            }

            if ($header_key === self::OOS_CONTENT_LENGTH) {
                unset($newHeaders[$header_value]);
                if(isset($options[self::OOS_CONTENT])){
                    $contentLength = strlen($options[self::OOS_CONTENT]);
                    if($contentLength != $header_value){
                        throw new OosException("The Content-Length is not correct.");
                    }
                }
                else{

                }
            }
            else if ($header_key === self::OOS_ACCEPT_EXPECT){
                if(self::OOS_ACCEPT_EXPECT_VALUE != $header_value){
                    throw new OosException("The Expect value:" . $header_value ." is not correct.");
                }
            }
            else if ($header_key === self::OOS_X_AMZ_STORAGE_CLASS){
                if("STANDARD" != $header_value && "REDUCED_REDUNDANCY" != $header_value){
                    throw new OosException("The x-amz-storage-class value:" . $header_value ." is not correct.");
                }
            }
        }
        return $newHeaders;
    }

    /**
     * Generates UserAgent
     *
     * @return string
     */
    private function generateUserAgent()
    {
        return self::OOS_NAME . "/" . self::OOS_VERSION . " (" . php_uname('s') . "/" . php_uname('r') . "/" . php_uname('m') . ";" . PHP_VERSION . ")";
    }

    /**
     * Checks endpoint type and returns the endpoint without the protocol schema.
     * Figures out the domain's type (ip, cname or private/public domain).
     *
     * @param string $endpoint
     * @param boolean $isCName
     * @return string The domain name without the protocol schema.
     */
    private function checkEndpoint($endpoint, $isCName)
    {
        $ret_endpoint = null;
        if (strpos($endpoint, 'http://') === 0) {
            $ret_endpoint = substr($endpoint, strlen('http://'));
        } elseif (strpos($endpoint, 'https://') === 0) {
            $ret_endpoint = substr($endpoint, strlen('https://'));
            $this->useSSL = true;
        } else {
            $ret_endpoint = $endpoint;
        }

        if ($isCName) {
            $this->hostType = self::OOS_HOST_TYPE_CNAME;
        } elseif (OosUtil::isIPFormat($ret_endpoint)) {
            $this->hostType = self::OOS_HOST_TYPE_IP;
        } else {
            $this->hostType = self::OOS_HOST_TYPE_NORMAL;
        }
        return $ret_endpoint;
    }

    /**
     * Check if all dependent extensions are installed correctly.
     * For now only "curl" is needed.
     * @throws 
     */
    public static function checkEnv()
    {
        if (function_exists('get_loaded_extensions')) {
            //Test curl extension
            $enabled_extension = array("curl");
            $extensions = get_loaded_extensions();
            if ($extensions) {
                foreach ($enabled_extension as $item) {
                    if (!in_array($item, $extensions)) {
                        throw new OosException("Extension {" . $item . "} is not installed or not enabled, please check your php env.");
                    }
                }
            } else {
                throw new OosException("function get_loaded_extensions not found.");
            }
        } else {
            throw new OosException('Function get_loaded_extensions has been disabled, please check php config.');
        }
    }

    /**
     * Sets the http's timeout (in seconds)
     *
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Sets the http's connection timeout (in seconds)
     *
     * @param int $connectTimeout
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    private function SHA256Hex($str){
        $result = hash('sha256', $str, false);
       // return bin2hex($re); true
        return $result;
    }

    const OOS_SERVICE_NAME = "s3";
    const OOS_SERVICE = "OOS_SERVICE";
    const OOS_REQUEST_NAME = "aws4_request";
    const OOS_REGION = 'region';

    // Constants for Life cycle
    const OOS_LIFECYCLE_EXPIRATION = "Expiration";
    const OOS_LIFECYCLE_TIMING_DAYS = "Days";
    const OOS_LIFECYCLE_TIMING_DATE = "Date";

    //OOS Internal constants
    const OOS_BUCKET = 'bucket';
    const OOS_OBJECT = 'object';
    const OOS_HEADERS = OosUtil::OOS_HEADERS;
    const OOS_METHOD = 'method';
    const OOS_QUERY = 'query';
    const OOS_BASENAME = 'basename';
    const OOS_MAX_KEYS = 'max-keys';
    const OOS_UPLOAD_ID = 'uploadId';
    const OOS_PART_NUM = 'partNumber';
    const OOS_COMP = 'comp';
    const OOS_LIVE_CHANNEL_STATUS = 'status';
    const OOS_LIVE_CHANNEL_START_TIME = 'startTime';
    const OOS_LIVE_CHANNEL_END_TIME = 'endTime';
    const OOS_POSITION = 'position';
    const OOS_MAX_KEYS_VALUE = 100;
    const OOS_MAX_OBJECT_GROUP_VALUE = OosUtil::OOS_MAX_OBJECT_GROUP_VALUE;
    const OOS_MAX_PART_SIZE = OosUtil::OOS_MAX_PART_SIZE;
    const OOS_MID_PART_SIZE = OosUtil::OOS_MID_PART_SIZE;
    const OOS_MIN_PART_SIZE = OosUtil::OOS_MIN_PART_SIZE;
    const OOS_FILE_SLICE_SIZE = 8192;
    const OOS_PREFIX = 'prefix';
    const OOS_DELIMITER = 'delimiter';
    const OOS_MARKER = 'marker';
    const OOS_ACCEPT_ENCODING = 'Accept-Encoding';
    const OOS_ACCEPT_EXPECT = 'Expect';
    const OOS_ACCEPT_EXPECT_VALUE = '100-continue';
    const OOS_CONTENT_MD5 = 'Content-MD5';
    const OOS_SELF_CONTENT_MD5 = 'x-amz-meta-md5';
    const OOS_CTYUN_DATA_LOCATION = 'x-ctyun-data-location';
    const OOS_X_AMZ_STORAGE_CLASS = 'x-amz-storage-class';

    const OOS_CONTENT_TYPE = 'Content-Type';
    const OOS_CONTENT_LENGTH = 'Content-Length';
    const OOS_IF_MODIFIED_SINCE = 'If-Modified-Since';
    const OOS_IF_UNMODIFIED_SINCE = 'If-Unmodified-Since';
    const OOS_IF_MATCH = 'If-Match';
    const OOS_IF_NONE_MATCH = 'If-None-Match';
    const OOS_CACHE_CONTROL = 'Cache-Control';
    const OOS_EXPIRES = 'Expires';
    const OOS_PREAUTH = 'preauth';
    const OOS_CONTENT_COING = 'Content-Coding';
    const OOS_CONTENT_DISPOSTION = 'Content-Disposition';
    const OOS_RANGE = 'range';
    const OOS_ETAG = 'etag';
    const OOS_LAST_MODIFIED = 'lastmodified';
    const OS_CONTENT_RANGE = 'Range';
    const OOS_CONTENT = OosUtil::OOS_CONTENT;
    const OOS_BODY = 'body';
    const OOS_LENGTH = OosUtil::OOS_LENGTH;
    const OOS_HOST = 'Host';
    const OOS_DATE = 'Date';

    const OOS_USER_AGENT = 'User-Agent';
    const OOS_HEAD_DATE = 'x-amz-date';
    const OOS_SIGNED_HEADERS = 'SignedHeaders';
    const OOS_CONTENT_SHA256 = 'x-amz-content-sha256';
    const OOS_CONTENT_NO_PAYLOAD = 'UNSIGNED-PAYLOAD';
    const EMPTY_BODY_SHA256 = "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855";
    const OOS_HMAC_SHA256 = 'AWS4-HMAC-SHA256';

    const OOS_AUTHORIZATION = 'Authorization';

    const OOS_FILE_DOWNLOAD = 'fileDownload';
    const OOS_FILE_UPLOAD = 'fileUpload';
    const OOS_PART_SIZE = 'partSize';
    const OOS_SEEK_TO = 'seekTo';
    const OOS_SIZE = 'size';
    const OOS_QUERY_STRING = 'query_string';
    const OOS_SUB_RESOURCE = 'sub_resource';
    const OOS_DEFAULT_PREFIX = 'x-amz-';
    const OOS_CHECK_MD5 = 'checkmd5';
    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';
    const OOS_SYMLINK_TARGET = 'x-amz-symlink-target';
    const OOS_SYMLINK = 'symlink';
    const OOS_HTTP_CODE = 'http_code';
    const OOS_REQUEST_ID = 'x-amz-request-id';
    const OOS_INFO = 'info';
    const OOS_STORAGE = 'storage';
    const OOS_RESTORE = 'restore';
    const OOS_STORAGE_STANDARD = 'Standard';
    const OOS_STORAGE_IA = 'IA';
    const OOS_STORAGE_ARCHIVE = 'Archive';

    //private URLs
    const OOS_URL_ACCESS_KEY_ID = 'AWSAccessKeyId';
    const OOS_URL_EXPIRES = 'Expires';
    const OOS_URL_SIGNATURE = 'Signature';

    //HTTP METHOD
    const OOS_HTTP_GET = 'GET';
    const OOS_HTTP_PUT = 'PUT';
    const OOS_HTTP_HEAD = 'HEAD';
    const OOS_HTTP_POST = 'POST';
    const OOS_HTTP_DELETE = 'DELETE';
    const OOS_HTTP_OPTIONS = 'OPTIONS';
    //Others

    const OOS_LIMITRATE ='x-amz-limitrate';
    const OOS_ACL = 'x-amz-acl';
    const OOS_OBJECT_ACL = 'x-amz-object-acl';
    const OOS_OBJECT_GROUP = 'x-amz-file-group';
    const OOS_MULTI_PART = 'uploads';
    const OOS_MULTI_DELETE = 'delete';
    const OOS_OBJECT_COPY_SOURCE = 'x-amz-copy-source';
    const OOS_OBJECT_COPY_SOURCE_RANGE = "x-amz-copy-source-range";
    const OOS_PROCESS = "x-amz-process";
    const OOS_CALLBACK = "x-amz-callback";
    const OOS_CALLBACK_VAR = "x-amz-callback-var";
    //Constants for STS SecurityToken
    const OOS_SECURITY_TOKEN = "x-amz-security-token";
    const OOS_ACL_TYPE_PRIVATE = 'private';
    const OOS_ACL_TYPE_PUBLIC_READ = 'public-read';
    const OOS_ACL_TYPE_PUBLIC_READ_WRITE = 'public-read-write';
    const OOS_ENCODING_TYPE = "encoding-type";
    const OOS_ENCODING_TYPE_URL = "url";

    const ACCESS_KEY_ACTION      = "Action";
    const CREATE_ACCESS_KEY      = "CreateAccessKey";
    const DELETE_ACCESS_KEY      = "DeleteAccessKey";
    const UPDATE_ACCESS_KEY      = "UpdateAccessKey";
    const LIST_ACCESS_KEY        = "ListAccessKey";
    const ACCESS_KEY_ID	         = "AccessKeyId";
    const ACCESS_KEY_STATUS      = "Status";
    const ACCESS_KEY_IS_PRIMARY  = "IsPrimary";

    const ACCESS_KEY_MAXITEM     = "MaxItems";
    const ACCESS_KEY_MARKER      = "Marker";

    // Domain Types
    const OOS_HOST_TYPE_NORMAL = "normal";//http://bucket.oss-cn-hangzhou.aliyuncs.com/object
    const OOS_HOST_TYPE_IP = "ip";  //http://1.1.1.1/bucket/object
    const OOS_HOST_TYPE_SPECIAL = 'special'; //http://bucket.guizhou.gov/object
    const OOS_HOST_TYPE_CNAME = "cname";  //http://mydomain.com/object
    //OOS ACL array
    static $OOS_ACL_TYPES = array(
        self::OOS_ACL_TYPE_PRIVATE,
        self::OOS_ACL_TYPE_PUBLIC_READ,
        self::OOS_ACL_TYPE_PUBLIC_READ_WRITE
    );
    // OosClient version information
    const OOS_NAME = "ctyun-sdk-php";
    const OOS_VERSION = "6.2.0";
    const OOS_BUILD = "20190718";
    const OOS_AUTHOR = "";
    const OOS_OPTIONS_ORIGIN = 'Origin';
    const OOS_OPTIONS_REQUEST_METHOD = 'Access-Control-Request-Method';
    const OOS_OPTIONS_REQUEST_HEADERS = 'Access-Control-Request-Headers';

    //是否是V5 Server
    private $isV5Server = false;

    //use ssl flag
    private $useSSL = false;
    private $maxRetries = 3;
    private $redirects = 0;

    // user's domain type. It could be one of the four: OOS_HOST_TYPE_NORMAL, OOS_HOST_TYPE_IP, OOS_HOST_TYPE_SPECIAL, OOS_HOST_TYPE_CNAME
    private $hostType = self::OOS_HOST_TYPE_NORMAL;
    private $requestUrl;
    private $requestProxy = null;
    private $accessKeyId;
    private $accessKeySecret;
    private $endpoint;
    private $hostname;
    private $securityToken;
    private $enableStsInUrl = false;
    private $timeout = 0;
    private $connectTimeout = 0;
}