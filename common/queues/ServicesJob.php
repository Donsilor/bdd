<?php


namespace common\queues;

use Yii;


/**
 * Class ServicesJob
 *
 */
class ServicesJob extends Job
{
    public $delay = 60;

    public $service;
    public $method;
    public $arguments;


    public function __get($service)
    {
        return new static([
            'service' => $service
        ]);
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($method, $arguments)
    {
        if($this->service) {
            $this->method = $method;
            $this->arguments = $arguments;
            Yii::$app->queue->delay($this->delay)->push($this);
        }
    }

    public function execute($queue) {
        $service = Yii::$app->services->{$this->service};

        if($service && $this->method && method_exists($service, $this->method)) {
            call_user_func([$service, $this->method], ...$this->arguments);
        }
    }

}