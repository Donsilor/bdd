<?php

namespace common\enums;

use services\goods\GoodsService;

/**
 * Class AppEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class OrderFromEnum extends BaseEnum
{
    const WEB_HK = 10;
    const MOBILE_HK = 11;
    const WEB_CN = 20;
    const MOBILE_CN = 21;
    const WEB_US = 30;
    const MOBILE_US = 31;

    const GROUP_HK = 'HK';
    const GROUP_CN = 'CN';
    const GROUP_US = 'US';

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
                self::WEB_HK => '香港PC端',
                self::MOBILE_HK => '香港移动端', 
                self::WEB_CN => '大陆PC端',
                self::MOBILE_CN => '大陆移动端', 
                self::WEB_US => '美国PC端',
                self::MOBILE_US => '美国移动端', 
        ];
    }

    public static function groups()
    {
        return [
            self::GROUP_HK => '香港',
            self::GROUP_CN => '大陆',
            self::GROUP_US => '美国',
        ];
    }

    public static function platformsForGroup($group)
    {
        $groups = [
            self::GROUP_HK => [
                self::WEB_HK,
                self::MOBILE_HK
            ],
            self::GROUP_CN => [
                self::WEB_CN,
                self::MOBILE_CN
            ],
            self::GROUP_US => [
                self::WEB_US,
                self::MOBILE_US
            ],
        ];

        return $groups[$group]??[];
    }


    public static function groupsToAreaId($groupId)
    {
        $groups = [
            self::GROUP_HK => AreaEnum::HongKong,
            self::GROUP_CN => AreaEnum::China,
            self::GROUP_US => AreaEnum::Other,
        ];

        return $groups[$groupId]??'';
    }

    //平台到地区ID
    public static function platformToAreaId($platform)
    {
        $group = self::platformToGroup($platform);

        return self::groupsToAreaId($group);
    }

    //平台到组
    public static function platformToGroup($platform)
    {
        $platforms = [
            self::WEB_HK => self::GROUP_HK,
            self::MOBILE_HK => self::GROUP_HK,
            self::WEB_CN => self::GROUP_CN,
            self::MOBILE_CN => self::GROUP_CN,
            self::WEB_US => self::GROUP_US,
            self::MOBILE_US => self::GROUP_US,
        ];

        return $platforms[$platform]??'';
    }

    //平台到组
    public static function platformToGroupName($platform)
    {
        $groups = self::groups();
        $group = self::platformToGroup($platform);

        return $groups[$group]??'';
    }
    
    //国家ID，对应平台ID
    public static function countryIdToPlatforms($countryId)
    {
        $countryIds = [
                '7' => self::GROUP_CN,
                '278' => self::GROUP_HK,
                '279' => self::GROUP_HK,
                '280' => self::GROUP_HK,
        ];
        
        $group = $countryIds[$countryId]??self::GROUP_US;
        
        return self::platformsForGroup($group);
        
    }
}