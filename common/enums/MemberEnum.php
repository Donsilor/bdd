<?php

namespace common\enums;

/**
 * 状态枚举
 *
 * Class StatusEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class MemberEnum extends BaseEnum
{
    const MEMBER_TOURIST_NO = 0;
    const MEMBER_TOURIST_YES = 1;

    const MEMBER_BOOK_STATUS_UNANSWERED = 0;
    const MEMBER_BOOK_STATUS_REPLY = 1;
    const MEMBER_BOOK_STATUS_INVALID = 2;

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [

        ];
    }

    public static function getBookStatus():array {
        return [
            self::MEMBER_BOOK_STATUS_REPLY => '已回复',
            self::MEMBER_BOOK_STATUS_UNANSWERED => '未回复',
            self::MEMBER_BOOK_STATUS_INVALID => '无效',
        ];
    }

    public static function isTourist()
    {
        return [
            self::MEMBER_TOURIST_NO => '注册客户',
            self::MEMBER_TOURIST_YES => '游客',
        ];
    }

	
}