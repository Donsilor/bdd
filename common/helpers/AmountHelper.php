<?php


namespace common\helpers;


class AmountHelper
{
    /**
     * 汇率计算
     * @param unknown $amount
     * @param unknown $rate
     * @param unknown $scale
     * @param unknown $sep
     */
    public static function outputAmount($amount, $rate = 1, $scale = 2, $sep = ',')
    {
        return number_format(bcmul($amount, $rate, ($scale+1)), $scale, '.', $sep);
    }
    /**
     * 格式化金额
     * @param unknown $amount
     * @param number $scale
     * @param string $sep
     * @return string
     */
    public static function formatAmount($amount, $scale = 2, $sep = ',')
    {
        return number_format($amount, $scale, '.', $sep);
    }
    /**
     * 加价率计算
     * @param unknown $amount
     * @param unknown $markup_rate
     * @param unknown $markup_value
     * @param unknown $scale
     * @param string $sep
     */
    public static function calcMarkupPrice($amount, $markup_rate, $markup_value, $scale = 2,$sep = '')
    {
        return number_format(bcadd(bcmul($amount, $markup_rate, $scale+2),$markup_value,$scale+2), '.', $sep);
    }
}