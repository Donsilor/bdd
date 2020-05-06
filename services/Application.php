<?php

namespace services;

use common\components\Service;

/**
 * Class Application
 *
 * @package services
 * @property \services\merchant\MerchantService $merchant 商户
 * @property \services\merchant\MemberService $merchantMember 商户用户
 * @property \services\backend\BackendService $backend 系统
 * @property \services\backend\MemberService $backendMember 管理员
 * @property \services\backend\NotifyService $backendNotify 消息
 * @property \services\backend\NotifyPullTimeService $backendNotifyPullTime 消息拉取日志
 * @property \services\backend\NotifySubscriptionConfigService $backendNotifySubscriptionConfig 提醒配置
 * @property \services\api\AccessTokenService $apiAccessToken Api授权key
 * @property \services\member\MemberService $member 会员
 * @property \services\member\AuthService $memberAuth 会员第三方授权
 * @property \services\member\AccountService $memberAccount 会员账号
 * @property \services\member\AddressService $memberAddress 会员收货地址
 * @property \services\member\CreditsLogService $memberCreditsLog 会员积分/余额变动日志
 * @property \services\common\ActionLogService $actionLog 行为日志
 * @property \services\common\ActionBehaviorService $actionBehavior 可被记录的行为
 * @property \services\common\AttachmentService $attachment 公用资源
 * @property \services\common\MenuService $menu 菜单
 * @property \services\common\MenuCateService $menuCate 菜单分类
 * @property \services\common\LogService $log 公用日志
 * @property \services\common\PayService $pay 公用支付
 * @property \services\common\MailerService $mailer 公用邮件
 * @property \services\common\SmsService $sms 公用短信
 * @property \services\common\AddonsService $addons 插件
 * @property \services\common\AddonsConfigService $addonsConfig 插件配置
 * @property \services\common\AddonsBindingService $addonsBinding 插件菜单入口
 * @property \services\common\AuthItemService $authItem 权限
 * @property \services\common\AuthRoleService $authRole 角色
 * @property \services\common\AuthAssignmentService $authAssignment 授权
 * @property \services\common\AuthService $auth 权限验证
 * @property \services\common\ConfigService $config 基础配置
 * @property \services\common\ConfigCateService $configCate 基础配置分类
 * @property \services\common\ProvincesService $provinces ip黑名单
 * @property \services\common\AreaService $area 国际地区
 * @property \services\common\IpBlacklistService $ipBlacklist 省市区
 * @property \services\oauth2\ServerService $oauth2Server oauth2服务
 * @property \services\oauth2\ClientService $oauth2Client oauth2客户端
 * @property \services\oauth2\AccessTokenService $oauth2AccessToken oauth2授权token
 * @property \services\oauth2\RefreshTokenService $oauth2RefreshToken oauth2刷新token
 * @property \services\oauth2\AuthorizationCodeService $oauth2AuthorizationCode oauth临时code
 * 
 * @property \services\goods\CategoryService $goodsCate 商品分类
 * @property \services\goods\AttributeService $goodsAttribute 商品属性
 * @property \services\goods\TypeService $goodsType 商品类型（产品线）
 * @property \services\goods\DiamondService $diamond 裸钻
 * @property \services\goods\DiamondSourceService $diamondSource 裸钻来源
 * @property \services\goods\GoodsService $goods 商品
 * @property \services\goods\SalepolicyService $salepolicy 销售政策
 * @property \services\goods\StyleService $goodsStyle 款式
 * @property \services\common\AdvertService $advert 广告位
 * @property \services\common\CurrencyService $currency 货币汇率
 * @property \services\order\OrderService $order 订单
 * @property \services\order\OrderTouristService $orderTourist 订单
 * @property \services\common\ExpressService $express 快递
 * @property \services\order\OrderInvoiceService $orderInvoice 订单
 * @property \services\market\CardService $card 订单
 */
class Application extends Service
{
    /**
     * @var array
     */
    public $childService = [
        /** ------ 系统 ------ **/
        'backend' => 'services\backend\BackendService',
        'backendNotify' => 'services\backend\NotifyService',
        'backendNotifyPullTime' => 'services\backend\NotifyPullTimeService',
        'backendNotifySubscriptionConfig' => 'services\backend\NotifySubscriptionConfigService',
        'backendMember' => 'services\backend\MemberService',
        /** ------ 用户 ------ **/
        'member' => 'services\member\MemberService',
        'memberAuth' => 'services\member\AuthService',
        'memberAccount' => 'services\member\AccountService',
        'memberAddress' => 'services\member\AddressService',
        'memberCreditsLog' => 'services\member\CreditsLogService',
        /** ------ 商户 ------ **/
        'merchant' => 'services\merchant\MerchantService',
        'merchantMember' => 'services\merchant\MemberService',
        /** ------ api ------ **/
        'apiAccessToken' => [
            'class' => 'services\api\AccessTokenService',
            'cache' => false, // 启用缓存到缓存读取用户信息
            'timeout' => 720, // 缓存过期时间，单位秒
        ],
        /** ------ 公用部分 ------ **/
        'currency'=>'\services\common\CurrencyService', 
        'menu' => 'services\common\MenuService',
        'menuCate' => 'services\common\MenuCateService',
        'config' => 'services\common\ConfigService',
        'configCate' => 'services\common\ConfigCateService',
        'actionLog' => 'services\common\ActionLogService',
        'actionBehavior' => 'services\common\ActionBehaviorService',
        'ipBlacklist' => 'services\common\IpBlacklistService',
        'provinces' => 'services\common\ProvincesService',
        'area' => 'services\common\AreaService',
        'attachment' => 'services\common\AttachmentService',
        'addons' => 'services\common\AddonsService',
        'addonsConfig' => 'services\common\AddonsConfigService',
        'addonsBinding' => 'services\common\AddonsBindingService',
        'auth' => 'services\common\AuthService',
        'authItem' => 'services\common\AuthItemService',
        'authRole' => 'services\common\AuthRoleService',
        'authAssignment' => 'services\common\AuthAssignmentService',
        'log' => [
            'class' => 'services\common\LogService',
            'queueSwitch' => false, // 是否丢进队列
            'exceptCode' => [403] // 除了数组内的状态码不记录，其他按照配置记录
        ],
        'pay' => 'services\common\PayService',
        'jPush' => 'services\common\JPushService',
        'sms' => [
            'class' => 'services\common\SmsService',
            'queueSwitch' => false, // 是否丢进队列
        ],
        'mailer' => [
            'class' => 'services\common\MailerService',
            'queueSwitch' => false, // 是否丢进队列
        ],
        /** ------ oauth2 ------ **/
        'oauth2Server' => 'services\oauth2\ServerService',
        'oauth2Client' => 'services\oauth2\ClientService',
        'oauth2AccessToken' => 'services\oauth2\AccessTokenService',
        'oauth2RefreshToken' => 'services\oauth2\RefreshTokenService',
        'oauth2AuthorizationCode' => 'services\oauth2\AuthorizationCodeService',
        /*********商品相关*********/
        'goodsCate' => 'services\goods\CategoryService',
        'goodsAttribute' => 'services\goods\AttributeService',
        'goodsType' => 'services\goods\TypeService',        
        'goodsStyle' => 'services\goods\StyleService',
        'goods' => 'services\goods\GoodsService',
        'salepolicy' => 'services\goods\SalepolicyService',
        
        'diamond' => 'services\goods\DiamondService',
        'diamondSource' => 'services\goods\DiamondSourceService',
        'advert' => 'services\common\AdvertService',    
        /*********订单相关*********/
        'order' => 'services\order\OrderService',
        'orderTourist' => 'services\order\OrderTouristService',
        'orderInvoice' => 'services\order\OrderInvoiceService',
        'express' => 'services\common\ExpressService',


        'card' => 'services\market\CardService',
    ];
}