<?php
/**
 * Created by PhpStorm.
 * User: futur
 * Date: 2019/6/25
 * Time: 16:57
 */

namespace Kistate\OOS\Model;


class Grantee{
    private $uri = "";
    private $permission = "";

    public function __construct($uri,$permission)
    {
        $this->uri = $uri;
        $this->permission = $permission;
    }


    public function getUri()
    {
        return $this->uri;
    }
    public function getPermission()
    {
        return $this->permission;
    }
}