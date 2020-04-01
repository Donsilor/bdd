<?php
namespace Omnipay\Paypal;

use common\helpers\FileHelper;

class PaypalLog {
    
    /**
     * 日志写入
     * @param unknown $message
     * @param unknown $path
     */
    public static function writeLog($message,$fileName = null)
    {   
        $fileName = $fileName ? $fileName :"paypal-" . date('Y-m-d') . ".log";
        $message = "[".date('Y-m-d H:i:s')."]".$message;
        FileHelper::writeLog(self::logPath()."/".$fileName, $message);
    }
    /**
     * 日志目录
     * @return string
     */
    public static function logPath()
    {
        return \Yii::getAlias('@runtime') . "/pay-logs/paypal/".date('Y-m');
    }
    /**
     * 创建文件夹
     * @param unknown $path
     * @return boolean
     */
    public static function mkDirs($path)
    {
       return FileHelper::mkdirs($path);
    }
    
}