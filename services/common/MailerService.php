<?php

namespace services\common;

use Yii;
use yii\base\InvalidConfigException;
use common\components\Service;
use common\queues\MailerJob;
use common\models\common\EmailLog;
use yii\helpers\Json;
use common\enums\StatusEnum;
use yii\web\UnprocessableEntityHttpException;
use common\enums\SubscriptionReasonEnum;
use common\enums\SubscriptionActionEnum;
use common\enums\MessageLevelEnum;

/**
 * Class MailerService
 * @package services\common
 * @author jianyan74 <751393839@qq.com>
 */
class MailerService extends Service
{
    /**
     * 消息队列
     *
     * @var bool
     */
    public $queueSwitch = true;

    /**
     * @var array
     */
    protected $config = [];
    
    public function queue($queueSwitch = false)
    {
        $this->queueSwitch = $queueSwitch;
        return $this;
    }

    /**
     * 发送邮件
     *
     * ```php
     *       Yii::$app->services->mailer->send($user, $email, $subject, $template)
     * ```
     * @param object $user 用户信息
     * @param string $email 邮箱
     * @param string $subject 标题
     * @param string $template 对应邮件模板
     * @throws \yii\base\InvalidConfigException
     */
    public function send($email, $usage, $data = [], $language = null)
    {   

        $usageExplains = EmailLog::$usageExplain;        
        $usageTemplates = EmailLog::$usageTemplates;
        if(!$language) {
             $language = \Yii::$app->params['language'];
        }
        $subject  = $usageExplains[$usage]??'';
        $template = $usageTemplates[$usage]??'';
        if($language) {
            $template = 'languages/'.$language.'/'.$template;
        }
        $subject = Yii::t('mail', $subject,[],$language);
        $data['ip'] = Yii::$app->request->userIP;
        
        if ($this->queueSwitch == true) {
            $_params = array_merge(['data'=>$data],[
                    'email' => $email,
                    'subject' => $subject,
                    'template' => $template,
                    'usage'=>$usage
            ]);
            $messageId = Yii::$app->queue->push(new MailerJob($_params));

            return $messageId;
        }

        return $this->realSend($email, $subject , $template , $usage , $data);
    }

    /**
     * 发送
     *
     * @param $user
     * @param $email
     * @param $subject
     * @param $template
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function realSend($email, $subject, $template, $usage, $data = [])
    {
        try {
             $code = $data['code']??null;
             $member_id = $data['member_id']??0;
             $ip = $data['ip']??'';
             
             $this->setConfig();
            $send = Yii::$app->mailer
                ->compose($template, $data)
                ->setFrom([$this->config['smtp_username'] => $this->config['smtp_name']])
                ->setTo($email)
                ->setSubject($subject);

            //邮件上传附件
            if(isset($data['file'])){
                $file_content = isset($data['file']['file_content']) ? $data['file']['file_content'] : '';
                $file_ext = isset($data['file']['file_ext']) ? $data['file']['file_ext'] : 'txt';
                $contentType = isset($data['file']['contentType']) ? $data['file']['contentType'] : 'text/xml';
                $send->attachContent($file_content, [
                    'fileName'    => $subject.'.'.$file_ext,
                    'contentType' => $contentType
                ]);
            }
            $result = $send->send();
            
            $this->saveLog([
                    'title'=>$subject,
                    'email' => $email,
                    'code' => $code,
                    'member_id' => $member_id,
                    'usage' => $usage,
                    'ip' => $ip,
                    'error_code' => 200,
                    'error_msg' => 'ok',
                    'error_data' => Json::encode($result),
                    'status' => StatusEnum::ENABLED
            ]);
            return $data;
        } catch (InvalidConfigException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }catch (\Exception $e) {            
            
            $log = $this->saveLog([
                    'title'=>$subject,
                    'email' => $email,
                    'code' => $code,
                    'member_id' => $member_id,
                    'usage' => $usage,
                    'ip' =>$ip,
                    'error_code' => 422,
                    'error_msg' => '发送失败',
                    'error_data' => Json::encode($result),
                    'status' => StatusEnum::DISABLED
            ]);
            
            // 加入提醒池
            Yii::$app->services->backendNotify->createRemind(
                    $log->id,
                    SubscriptionReasonEnum::SMS_CREATE,
                    SubscriptionActionEnum::SMS_ERROR,
                    $log['member_id'],
                    MessageLevelEnum::getValue(MessageLevelEnum::ERROR) . "邮件：$log->error_data"
                    );
            
            throw new UnprocessableEntityHttpException('邮件发送失败');
        }

        return false;
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function setConfig()
    {
        $this->config = Yii::$app->debris->configAll();

        Yii::$app->set('mailer', [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => $this->config['smtp_host'],
                'username' => $this->config['smtp_username'],
                'password' => $this->config['smtp_password'],
                'port' => $this->config['smtp_port'],
                'encryption' => empty($this->config['smtp_encryption']) ? 'tls' : 'ssl',
            ],
        ]);
    }
    
    /**
     * @param $mobile
     * @return array|\yii\db\ActiveRecord|null
     */
    public function findByEmail($email)
    {
        return EmailLog::find()
            ->where(['email' => $email])
            ->orderBy('id desc')
            ->asArray()
            ->one();
    }
    
    /**
     * @param array $data
     * @return SmsLog
     */
    protected function saveLog($data = [])
    {
        $log = new EmailLog();
        $log = $log->loadDefaultValues();
        $log->attributes = $data;
        $log->save(false);
        return $log;
    }
}