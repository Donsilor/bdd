<?php

namespace common\enums;

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
}