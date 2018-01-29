<?php
/**
 * WxPay.php.
 * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/29 10:53
 * Desctiption:
 */

namespace ixiaomu\payment\gateways;

use ixiaomu\payment\Pay;
use ixiaomu\payment\exceptions\PayException;

class WxPay extends Pay
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
    /**
     * Wechat constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);
        if (is_null($this->userConfig->get('app_id'))) {
            throw new PayException('Missing Config -- [app_id]');
        }
        if (is_null($this->userConfig->get('mch_id'))) {
            throw new PayException('Missing Config -- [mch_id]');
        }
        if (is_null($this->userConfig->get('mch_key'))) {
            throw new PayException('Missing Config -- [mch_key]');
        }
        if (!empty($config['cache_path'])) {
            HttpService::$cachePath = $config['cache_path'];
        }

        $this->config = [
            'appid'      => $this->userConfig->get('app_id', ''),
            'mch_id'     => $this->userConfig->get('mch_id', ''),
            'nonce_str'  => $this->createNonceStr(),
            'sign_type'  => 'MD5',
            'notify_url' => $this->userConfig->get('notify_url', ''),
            'trade_type' => $this->getTradeType(),
        ];
    }

    /**
     * 统一下单
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     */
    protected function createOrder($options = []){
        return $this->getResult($options);
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
        $this->userConfig = array_merge($this->userConfig, $options);
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->url_refund, true);
    }

    /**
     * 撤销订单操作
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function reverse($out_trade_no = null)
    {
        $this->userConfig['out_trade_no'] = $out_trade_no;
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->url_reverse,true);
    }

    /**
     * 关闭正在进行的订单
     * @param string $out_trade_no
     * @return array
     * @throws Exception
     */
    public function close($out_trade_no = '')
    {
        $this->userConfig['out_trade_no'] = $out_trade_no;
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->url_close);
    }

    /**
     * 查询订单状态
     * @param string $out_trade_no
     * @return array
     * @throws Exception
     */
    public function find($out_trade_no = '')
    {
        $this->userConfig['out_trade_no'] = $out_trade_no;
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
        if ($this->getSign($data) === $sign){
            $result = $this->find($data['out_trade_no']);
            if (array_key_exists("return_code", $result)
                && array_key_exists("result_code", $result)
                && $result["return_code"] == "SUCCESS"
                && $result["result_code"] == "SUCCESS"
            ){
                return $data;
            }else{
                return false;
            }
        }
        return false;
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
        $this->userConfig['sign'] = $this->getSign($this->userConfig);
        if ($cert) {
            $data = $this->fromXml($this->postXmlCurl($url,$this->toXml($this->userConfig),true)); //需要证书
        } else {
            $data = $this->fromXml($this->postXmlCurl($url,$this->toXml($this->userConfig)));
        }

        if (isset($data['err_code']) && $data['err_code'] === 'USERPAYING'){
            return 'USERPAYING';  //需要用户输入支付密码
        }
        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS' || $data['result_code'] !== 'SUCCESS') {
            $error = 'WeChatError:' . $data['return_msg'];
            $error .= isset($data['err_code_des']) ? ' - ' . $data['err_code_des'] : '';
        }
        if(isset($data['sign'])) {
            if (!isset($error) && $this->getSign($data) !== $data['sign']) {
                $error = 'GetResultError: return data sign error';
            }
        }
        if (isset($error)) {
            throw new PayException($error);
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
        if (is_null($this->config['key'])) {
            throw new Exception('Missing Config -- [key]');
        }
        ksort($data);
        $string = md5($this->getSignContent($data) . '&key=' . $this->config['key']);
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
            throw new Exception('数组数据异常!');
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
        unset($this->userConfig['notify_url']);
        unset($this->userConfig['trade_type']);
        return true;
    }

    /**
     * 以post方式提交xml到对应的接口url
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param string $url url
     * @param string $xml 需要post的xml数据
     * @param bool $useCert 是否需要证书，默认不需要
     * @param null $proxy  是否需要代理
     * @param int $second url执行超时时间，默认30s
     * @return mixed
     * @throws PayException
     */

    public function postXmlCurl($url, $xml,  $useCert = false, $proxy = null, $second = 30)
    {
        //读取网址内容
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        //如果有配置代理这里就设置代理
        if($proxy){
            curl_setopt ($ch, CURLOPT_PROXY, $proxy);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $this->config['sslcert_path']);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $this->config['sslkey_path']);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new PayException("curl出错，错误码:$error");
        }
    }
}