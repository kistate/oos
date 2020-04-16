<?php
/**
 * Created by PhpStorm.
 * User: futur
 * Date: 2019/6/25
 * Time: 16:56
 */

namespace Kistate\OOS\Model;

class Initiator{
    private $id = "";
    private $displayName = "";

    public function __construct($id,$displayName)
    {
        $this->id = $id;
        $this->displayName = $displayName;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }
}