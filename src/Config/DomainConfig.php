<?php
/**
 * User: Kevin
 * Date: 2019/6/18
 * Time: 10:08
 */

namespace Kistate\OOS\Config;

class DomainConfig
{
    public static $S5SubDomainUpperCase = array();
    public static $S6RegionUpperCase = array();
    private static function initConfig(){
        //5.0 资源池
        self::$S5SubDomainUpperCase["OOS_JS"] = "OOS-JS";          //江苏
        self::$S5SubDomainUpperCase["OOS_BJ2"] = "OOS-BJ2";        //北京2
        self::$S5SubDomainUpperCase["OOS_HQ_BJ"] = "OOS-HQ-BJ";    //北京
        self::$S5SubDomainUpperCase["OOS_NM2"] = "OOS-NM2";        //内蒙
        self::$S5SubDomainUpperCase["OOS_SNXA"] = "OOS-SNXA";      //西安
        self::$S5SubDomainUpperCase["OOS_HQ_SH"] = "OOS-HQ-SH";    //上海
        self::$S5SubDomainUpperCase["OOS_HNCS"] = "OOS-HNCS";      //长沙
        self::$S5SubDomainUpperCase["OOS_FJ2"] = "OOS-FJ2";        //福建
        self::$S5SubDomainUpperCase["OOS_HZ"] = "OOS-HZ";          //杭州
        self::$S5SubDomainUpperCase["OOS_GZ"] = "OOS-GZ";          //广州

        //6.0 数据域
        self::$S6RegionUpperCase["ZhengZhou"] = "ZhengZhou";
        self::$S6RegionUpperCase["ShenYang"] = "ShenYang";
        self::$S6RegionUpperCase["ChengDu"] = "ChengDu";
        self::$S6RegionUpperCase["WuLuMuQi"] = "WuLuMuQi";
        self::$S6RegionUpperCase["LanZhou"] = "LanZhou";
        self::$S6RegionUpperCase["QingDao"] = "QingDao";
        self::$S6RegionUpperCase["GuiYang"] = "GuiYang";
        self::$S6RegionUpperCase["LaSa"] = "LaSa";
        self::$S6RegionUpperCase["WuHu"] = "WuHu";
        self::$S6RegionUpperCase["WuHan"] = "WuHan";
        self::$S6RegionUpperCase["ShenZhen"] = "ShenZhen";
        self::$S6RegionUpperCase["HaiKou"] = "HaiKou";
        self::$S6RegionUpperCase["JinHua"] = "JinHua";
        self::$S6RegionUpperCase["KunMing"] = "KunMing";
        self::$S6RegionUpperCase["ShiJiaZhuang"] = "ShiJiaZhuang";
    }

    /**
     * 判断Endpoint的资源池是否是5版本的
     * @endpoint String
     * @return boolean
     */
    public static function isS5Endpoint($endpoint){
        if($endpoint == null)
            return false;

        if(sizeof(self::$S5SubDomainUpperCase) == 0){
            self::initConfig();
        }

        $isS5 = false;
        foreach (self::$S5SubDomainUpperCase as $S5SubDomain) {
            if(strpos(strtoupper($endpoint),$S5SubDomain)  !== false){
                $isS5 = true;
                break;
            }
        }
        return $isS5;
    }

    public static function isValidRegion($region){
        if($region == null)
            return false;

        if(sizeof(self::$S5SubDomainUpperCase) == 0){
            self::initConfig();
        }
        foreach (self::$S6RegionUpperCase as $S6Region) {
            if(strtolower($S6Region) == strtolower($region)){
                return true;
            }
        }
        return false;
    }
}