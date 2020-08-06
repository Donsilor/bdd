<?php

namespace expresses\k5;

use GuzzleHttp\Client;

/**
 * Class ApiService
 * @method array searchOrderTracknumber(array $params)
 * @package expresses\k5
 */
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

    /**
     * @param $name
     * @param $arguments
     * @return array|boolean
     */
    public function __call($name, $arguments)
    {
        try {
            if (!in_array($name, $this->method)) {
                throw new \Exception($name." 方法不存在");
            }

            /**
             * @var string URL
             */
            $url = sprintf("%s%s", $this->baseUrl, $name);

            //参数
            $params = $this->getParams(...$arguments);

            $client = new Client();

            $response = $client->post($url, ['json' => $params]);

            //返回结果
            $result = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $exception) {
            $result = [
                'statusCode' => 'error',
                'message' => $exception->getMessage()
            ];
        }

        return $result;
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
    }
}