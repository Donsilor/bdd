<?php

namespace common\helpers;

use Yii;
use yii\helpers\Html;

/**
 * Class ImageHelper
 * @package common\helpers
 * @author jianyan74 <751393839@qq.com>
 */
class ImageHelper
{
    /**
     * 默认头像
     *
     * @param $imgSrc
     */
    public static function defaultHeaderPortrait($imgSrc, $defaultImgSre = '/resources/img/profile_small.jpg')
    {
        return !empty($imgSrc) ? $imgSrc : Yii::getAlias('@web') . $defaultImgSre;
    }

    /**
     * 点击大图
     *
     * @param string $imgSrc
     * @param int $width 宽度 默认45px
     * @param int $height 高度 默认45px
     */
    public static function fancyBox($imgSrc, $width = 60, $height = 60)
    {
        
        $thumb = $imgSrc."?x-oss-process=image/auto-orient,1/resize,m_lfit,w_{$width}/quality,q_90";
        $image = Html::img($thumb, [
            'width' => $width,
            'height' => $height,
        ]);
        return Html::a($image, $imgSrc, [
            'data-fancybox' => 'gallery'
        ]);
    }

    /**
     * 判断是否图片地址
     *
     * @param string $imgSrc
     * @return bool
     */
    public static function isImg($imgSrc)
    {
        $extend = StringHelper::clipping($imgSrc, '.', 1);

        $imgExtends = [
            'bmp',
            'jpg',
            'gif',
            'jpeg',
            'jpe',
            'jpg',
            'png',
            'jif',
            'dib',
            'rle',
            'emf',
            'pcx',
            'dcx',
            'pic',
            'tga',
            'tif',
            'tiffxif',
            'wmf',
            'jfif'
        ];
        if (in_array($extend, $imgExtends) || strpos($imgSrc, 'http://wx.qlogo.cn') !== false) {
            return true;
        }

        return false;
    }
    /**
     * 商品缩略图
     * @param unknown $godos_image
     * @param string $size
     * @return string
     */
    public static function goodsThumb($image,$size = '')
    {
        if($size == 'small'){
            return self::thumb($image,400,400);
        }elseif($size == 'mid') {
            return self::thumb($image,400,400);
        }else if($size == 'big'){
            return self::thumb($image,800,800);
        }else{
            return self::thumb($image);
        }
    }
    /**
     * 批量缩略图
     * @param unknown $images
     * @param string $size
     * @return unknown
     */
    public static function goodsThumbs($images,$size = '')
    {
        $images = explode(',',$images);
        if(!empty($images) && is_array($images)){
            foreach ($images as $k=> $image){
                $images[$k] = self::goodsThumb($image,$size);
            }
        }
        $images = join(',',$images);
        return $images;
    }
    /**
     * 缩略图
     * @param unknown $image
     * @param string $width
     * @param string $height
     * @return string
     */
    public static function thumb($image ,$width = '',$height = '')
    {   
        if($width > 0) {
            $height = $width;
            $image .= "?x-oss-process=style/{$width}X{$height}";
        }        
        return $image;
    }
    
    public static function thumbs($images ,$width = '',$height = '')
    {
        if(!empty($images) && is_array($images)){
            foreach ($images as &$image){
                self::thumb($image,$width,$height);
            }
        }        
        return $images;
    }
}