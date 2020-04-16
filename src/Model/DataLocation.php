<?php
/**
 * Created by PhpStorm.
 * User: futur
 * Date: 2019/6/20
 * Time: 14:20
 */

namespace Kistate\OOS\Model;
use Kistate\OOS\Config\DomainConfig;
use Kistate\OOS\Core\OosException;

class DataLocation
{
    public function __construct($type,$locationList,$scheduleStrategy)
    {
        //Type
        if(!isset($type) || $type == null){
            Print( ' Data Location\'s type is not specified.\n');
            $this->type = "Local";
        }
        else{
            if(strtolower($type) == strtolower("Local") || strtolower($type) == strtolower("Specified")){
                $this->type = $type;
            }
            else{
                throw new OosException( 'Type:' . $type . ' is invalid.');
            }
        }

        //ScheduleStrategy
        if(!isset($scheduleStrategy) || $scheduleStrategy == null){
            Print( ' Data Location\'s scheduleStrategy is not specified.\n');
            $this->scheduleStrategy = "Allowed";
        }
        else{
            if(strtolower($scheduleStrategy) == strtolower("Allowed") || strtolower($scheduleStrategy) == strtolower("NotAllowed")){
                $this->scheduleStrategy = $scheduleStrategy;
            }
            else{
                throw new OosException( 'ScheduleStrategy:' . $scheduleStrategy . ' is invalid.');
            }
        }

        //locationList
        if(!isset($locationList) || $locationList == null || sizeof($locationList)<1) {
            //throw new OosException( ' locationList\'s size is zero.');
        }
        else{
            //客户端不判断region的有效性
            /*
            foreach ($locationList as $location ){
                if(!DomainConfig::isValidRegion($location)){
                    throw new OosException( 'Region:' . $location . '  is invalid region.');
                }
            }
            */
            $this->locationList = $locationList;
        }
    }

    public function getLocationList(){
        return $this->locationList;
    }
    public function getType(){
        return $this->type;
    }

    public function getScheduleStrategy(){
        return $this->scheduleStrategy;
    }

    //类型：key-value形式
    //有效值：type=[Local|Specified],
    //location=[ZhengZhou|ShenYang|ChengDu|WuLuMuQi|LanZhou|QingDao|GuiYang|LaSa|WuHu|WuHan|ShenZhen],
    //scheduleStrategy=[Allowed|NotAllowed]
    //type=local表示就近写入本地。type= Specified表示指定位置。
    //location表示指定的数据位置，可以填写多个，以逗号分隔。
    //scheduleStrategy表示调度策略，是否允许OOS自动调度数据存储位置
    public function toKeyValueString()
    {
        $keyValues = "";
        $locations = "";
        foreach ($this->locationList as $location ){
            if(strlen($locations)>0)
                $locations = $locations . "," . $location;
            else
                $locations = $locations . $location;
        }
        $keyValues = $keyValues . "type=" . $this->type . ",";
        $keyValues = $keyValues . "location=" . $locations . ",";
        $keyValues = $keyValues . "scheduleStrategy=" . $this->scheduleStrategy;

        return $keyValues;
    }

    private $type;
    private $locationList;
    private $scheduleStrategy;
}