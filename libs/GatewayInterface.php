<?php
/**
 * GatewayInterface.php.
 * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/29 13:08
 * Desctiption:
 */
namespace ixiaomu\payment\lib;

abstract class GatewayInterface
{
    /**
     * 发起支付
     * @param array $options
     * @return mixed
     */
    abstract public function apply(array $options);

    /**
     * 订单退款
     * @param $options
     * @return mixed
     */
    abstract public function refund($options);

    /**
     * 关闭订单
     * @param $options
     * @return mixed
     */
    abstract public function close($options);

    /**
     * 查询订单
     * @param $out_trade_no
     * @return mixed
     */
    abstract public function find($out_trade_no);

    /**
     * 通知验证
     * @param array $data
     * @param null $sign
     * @param bool $sync
     * @return mixed
     */
    abstract public function verify($data, $sign = null, $sync = false);

    /**
     * 网络模拟请求
     * @param string $url 网络请求URL
     * @param array|string $data 请求数据
     * @param array $options
     * @return bool|string
     */
    public function post($url, $data, $options = [])
    {
        return HttpService::post($url, $data, $options);
    }
}