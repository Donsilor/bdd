<?php

namespace expresses\k5;

class ApiService
{
    /**
     * 方法列表
     * @var string[]
     */
    private $method = [
        'searchOrderTracknumber',
    ];

    /**
     * url
     * @var string
     */
    private $baseUrl = "http://hcjy.kingtrans.net/PostInterfaceService?method=";

    private $config = [
        'Clientid' => 'KYD',
        'Token' => 'rWfj56YwWxdrtuUIGgCW',
    ];

    public function __get($name)
    {
        // TODO: Implement __get() method.
    }

    /**
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if(!in_array($name, $this->method)) {
            throw new \Exception("方法不存在");
        }

        /**
         * @var string URL
         */
        $url = sprintf("%s%s", $this->baseUrl, $name);

        //参数
        $params = $this->getParams($arguments[0]??[]);

        //返回结果

        // TODO: Implement __call() method.
    }

    /**
     * @param array $params
     * @return string|array
     */
    private function getParams($params)
    {
        $data = [];
        $data['Verify'] = $this->config;
        $data += $params;

        return $data;

//        return \GuzzleHttp\json_encode($data);
    }

}