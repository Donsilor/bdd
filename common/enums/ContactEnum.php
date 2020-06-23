<?php

namespace common\enums;

/**
 * 状态枚举
 *
 * Class StatusEnum
 * @package common\enums
 * @author jianyan74 <751393839@qq.com>
 */
class ContactEnum extends BaseEnum
{
	const CONTACT_TYPE_RINT = 1;
	const CONTACT_TYPE_RIGHT= 2;
    const CONTACT_TYPE_ORNAMENTS = 3;
    const CONTACT_TYPE_GENERALIZE = 4;

    const ENABLED = 1;
    const DISABLED = 0;

	/**
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
            self::CONTACT_TYPE_RINT => '订婚戒指',
            self::CONTACT_TYPE_RIGHT => '结婚对戒',
            self::CONTACT_TYPE_ORNAMENTS => '时尚饰品',
            self::CONTACT_TYPE_GENERALIZE => '大陆莫桑石推广页0622',
		];
	}

}