<?php

namespace Kistate\OOS;

use Log;
use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class OOSFileSystemServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('oos', function($app, $config)
        {
            $accessId  = $config['access_id'];
            $accessKey = $config['access_key'];

            $cdnDomain = empty($config['cdnDomain']) ? '' : $config['cdnDomain'];
            $bucket    = $config['bucket'];
            $ssl       = empty($config['ssl']) ? false : $config['ssl'];
            $isCname   = empty($config['isCName']) ? false : $config['isCName'];
            $debug     = empty($config['debug']) ? false : $config['debug'];

            $endPoint  = $config['endpoint']; // 默认作为外部节点
            $epInternal= $isCname?$cdnDomain:(empty($config['endpoint_internal']) ? $endPoint : $config['endpoint_internal']); // 内部节点

            if($debug) Log::debug('OOS config:', $config);

            $client  = new OosClient($accessId, $accessKey, $epInternal, $isCname);
            $adapter = new OosAdapter($client, $bucket, $endPoint, $ssl, $isCname, $debug, $cdnDomain);

            //Log::debug($client);
            $filesystem =  new Filesystem($adapter);

//            $filesystem->addPlugin(new PutFile());
//            $filesystem->addPlugin(new PutRemoteFile());
            //$filesystem->addPlugin(new CallBack());
            return $filesystem;
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
