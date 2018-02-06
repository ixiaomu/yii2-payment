<?php
/**
 * Wechat.phpp.
 * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/29 10:53
 * Desctiption:
 */

namespace ixiaomu\payment\gateways;

use ixiaomu\payment\libs\GatewayInterface;
use ixiaomu\payment\exceptions\PayException;

abstract class Wechat extends GatewayInterface
{

    protected $config;

    protected $userConfig;

    protected $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';  //统一下单
    protected $url_query = 'https://api.mch.weixin.qq.com/pay/orderquery'; //订单查询
    protected $url_close = 'https://api.mch.weixin.qq.com/pay/closeorder'; //关闭订单
    protected $url_refund = 'https://api.mch.weixin.qq.com/secapi/pay/refund'; //退款申请
    protected $url_transfer = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers'; //企业付款
    protected $url_micropay = 'https://api.mch.weixin.qq.com/pay/micropay'; //刷卡支付
    protected $url_bill = 'https://api.mch.weixin.qq.com/pay/downloadbill';
    protected $url_reverse = 'https://api.mch.weixin.qq.com/secapi/pay/reverse'; //撤销订单


    public function __construct(array $config)
    {

        $this->userConfig = $config;
        if (is_null($this->userConfig['app_id'])) {
            throw new PayException('Missing Config -- [app_id]');
        }
        if (is_null($this->userConfig['mch_id'])) {
            throw new PayException('Missing Config -- [mch_id]');
        }
        if (is_null($this->userConfig['mch_key'])) {
            throw new PayException('Missing Config -- [mch_key]');
        }
        $this->config = [
            'appid'      => $this->userConfig['app_id'],
            'mch_id'     => $this->userConfig['mch_id'],
            'nonce_str'  => $this->createNonceStr(),
            'sign_type'  => 'MD5',
            'notify_url' => $this->userConfig['notify_url']
        ];
        if (isset($this->userConfig['sub_mch_id'])){
            $this->config['sub_appid'] = $this->userConfig['sub_appid'];
            $this->config['sub_mch_id'] = $this->userConfig['sub_mch_id'];
        }
    }

    /**
     * 退款
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param array $options
     * @return mixed
     */
    public function refund($options = [])
    {
        $this->config = array_merge($this->config, $options);
        $this->config['op_user_id'] = isset($this->config['op_user_id']) ?: $this->userConfig['mch_id'];
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->url_refund, true);
    }

    /**
     * 撤销订单操作
     * @param array $options
     * @return array
     * @throws PayException
     */
    public function reverse($out_trade_no = null)
    {
        $this->config['out_trade_no'] = $out_trade_no;
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->url_reverse,true);
    }

    /**
     * 关闭正在进行的订单
     * @param string $out_trade_no
     * @return array
     * @throws PayException
     */
    public function close($out_trade_no = '')
    {
        $this->config['out_trade_no'] = $out_trade_no;
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->url_close);
    }

    /**
     * 查询订单状态
     * @param string $out_trade_no
     * @return array
     * @throws PayException
     */
    public function find($out_trade_no = '')
    {
        $this->config['out_trade_no'] = $out_trade_no;
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->url_query);
    }

    /**
     * XML内容验证
     * @param string $data
     * @param null $sign
     * @param bool $sync
     * @return array|bool
     */
    public function verify($data, $sign = null, $sync = false)
    {
        $data = $this->fromXml($data);
        $sign = is_null($sign) ? $data['sign'] : $sign;
        return $this->getSign($data) === $sign ? $data : false;
    }

    /**
     * preOrder
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param array $options
     * @return string
     * @throws PayException
     */
    public function preOrder($options =[]){
        $this->config = array_merge($this->config, $options);
        return $this->getResult($this->url);
    }

    /**
     * 请求
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param $url
     * @param bool $cert 是否使用证书 false 不使用
     * @return string
     */
    protected function getResult($url, $cert = false)
    {
        $this->config['sign'] = $this->getSign($this->config);
        if ($cert) {
            $data = $this->fromXml($post = $this->post($url, $this->toXml($this->config), ['ssl_cer' => $this->userConfig['sslcert_path'], 'ssl_key' => $this->userConfig['sslkey_path']]));
        } else {
            $data = $this->fromXml($this->post($url, $this->toXml($this->config)));
        }
        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS' || $data['result_code'] !== 'SUCCESS') {
            $error = 'WeChatError:' . $data['return_msg'];
            $error .= isset($data['err_code_des']) ? ' - ' . $data['err_code_des'] : '';
        }
        if (!isset($error) && $this->getSign($data) !== $data['sign']) {
            $error = 'WeChatError: return data sign error';
        }
        if (isset($error)) {
            throw new PayException($error, 20000);
        }
        return $data;
    }

    /**
     * 生成内容签名
     * @param $data
     * @return string
     */
    protected function getSign($data)
    {
        if (is_null($this->userConfig['mch_key'])) {
            throw new PayException('Missing Config -- [mch_key]');
        }
        ksort($data);
        $string = md5($this->getSignContent($data) . '&key=' . $this->userConfig['mch_key']);
        return strtoupper($string);
    }

    /**
     * 生成签名内容
     * @param $data
     * @return string
     */
    private function getSignContent($data)
    {
        $buff = '';
        foreach ($data as $k => $v) {
            $buff .= ($k != 'sign' && $v != '' && !is_array($v)) ? $k . '=' . $v . '&' : '';
        }
        return trim($buff, '&');
    }

    /**
     * 生成随机字符串
     * @param int $length
     * @return string
     */
    protected function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 转为XML数据
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param $data
     * @return string
     */
    protected function toXml($data)
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new PayException('数组数据异常!');
        }
        $xml = "<xml>";
        foreach ($data as $key => $val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 解析XML数据
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param $xml
     * @return mixed
     */
    protected function fromXml($xml)
    {
        if (!$xml) {
            throw new PayException('convert to array error !invalid xml');
        }
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 清理签名验证不必要的参数
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @return bool
     */
    protected function unsetTradeTypeAndNotifyUrl()
    {
        unset($this->config['notify_url']);
        unset($this->config['trade_type']);
        return true;
    }

}