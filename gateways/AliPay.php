<?php
/**
 * Alipay.php.
 * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/29 10:54
 * Desctiption:  支付宝
 */

namespace ixiaomu\payment\gateways;

use ixiaomu\payment\exceptions\PayException;
use ixiaomu\payment\libs\GatewayInterface;

abstract class Alipay extends GatewayInterface
{

    protected $config;

    protected $userConfig;

    protected $gateway = 'https://openapi.alipay.com/gateway.do?charset=utf-8'; //支付宝网关地址

    /**
     * Alipay constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->userConfig = $config;
        if (is_null($this->userConfig['app_id'])) {
            throw new PayException('Missing Config -- [app_id]');
        }
        $this->config = [
            'app_id'      => $this->userConfig['app_id'],
            'method'      => '',
            'format'      => 'json',
            'charset'     => 'utf-8',
            'sign_type'   => 'RSA2',
            'version'     => '1.0',
            'return_url'  => $this->userConfig['return_url'],
            'notify_url'  => $this->userConfig['notify_url'],
            'timestamp'   => date('Y-m-d H:i:s'),
            'app_auth_token' => $this->userConfig['app_auth_token'],
            'sign'        => '',
            'biz_content' => '',
        ];
    }

    /**
     * 应用参数
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param array $options
     * @return mixed|void
     */
    public function apply(array $options)
    {
        $options['product_code'] = $this->getProductCode();
        $this->config['method'] = $this->getMethod();
        $this->config['biz_content'] = json_encode($options);
        $this->config['sign'] = $this->getSign();
    }

    /**
     * 支付宝订单退款操作
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param array|string $options 退款参数或退款商户订单号
     * @param null $refund_amount 退款金额
     * @return array|bool
     * @throws PayException
     */
    public function refund($options, $refund_amount = null)
    {
        if (!is_array($options)) {
            $options = ['out_trade_no' => $options, 'refund_amount' => $refund_amount];
        }
        return $this->getResult($options, 'alipay.trade.refund');
    }

    /**
     * 关闭支付宝进行中的订单
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param $options
     * @return array|bool
     * @throws PayException
     */
    public function close($options)
    {
        if (!is_array($options)) {
            $options = ['out_trade_no' => $options];
        }
        return $this->getResult($options, 'alipay.trade.close');
    }

    /**
     * 撤销订单操作
     * @param array $options
     * @return array
     * @throws PayException
     */
    public function reverse($options)
    {
        if (!is_array($options)) {
            $options = ['out_trade_no' => $options];
        }
        return $this->getResult($options, 'alipay.trade.cancel');
    }

    /**
     * 查询支付宝订单状态
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param string $out_trade_no
     * @return array|bool
     * @throws PayException
     */
    public function find($out_trade_no = '')
    {
        $options = ['out_trade_no' => $out_trade_no];
        return $this->getResult($options, 'alipay.trade.query');
    }

    /**
     * 验证支付宝支付宝通知
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param $data
     * @param null $sign
     * @param bool $sync
     * @return bool
     */
    public function verify($data, $sign = null, $sync = false)
    {
        if (is_null($this->userConfig['public_key'])) {
            throw new PayException('Missing Config -- [public_key]');
        }
        $sign = is_null($sign) ? $data['sign'] : $sign;
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($this->userConfig['public_key'], 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $toVerify = $sync ? json_encode($data) : $this->getSignContent($data, true);
        $response = openssl_verify($toVerify, base64_decode($sign), $res, OPENSSL_ALGO_SHA256) === 1 ? $data : false;
        if($response !== false){
            if ((!empty($response)&&("10000" == $response['code']))) {
                // 支付交易明确成功
                $result['status'] = 'SUCCESS';
                //查询订单操作
                if(isset($response['trade_status']) && $response['trade_status'] == 'TRADE_SUCCESS'){
                    $result['status'] = 'SUCCESS';
                }
                if((isset($response['trade_status']) && $response['trade_status'] == 'WAIT_BUYER_PAY')){
                    $result['status'] = 'WAIT';
                }
                if((isset($response['trade_status']) && $response['trade_status'] == 'TRADE_CLOSED')){
                    $result['status'] = 'FAILED';
                }
            } elseif ((!empty($response)&&("10003" == $response['code']))) {
                // 返回用户处理中，则轮询查询交易是否成功
                $result['status'] = 'WAIT';
            }else {
                // 其他情况表明该订单支付明确失败
                $result['status'] = 'FAILED';
            }
            $result['body'] = $response;
            return $result;
        }
    }

    abstract protected function getMethod();

    abstract protected function getProductCode();

    protected function buildPayHtml()
    {
        $html = "<form id='alipaysubmit' name='alipaysubmit' action='{$this->gateway}' method='post'>";
        foreach ($this->config as $key => $value) {
            $value = str_replace("'", '&apos;', $value);
            $html .= "<input type='hidden' name='{$key}' value='{$value}'/>";
        }
        $html .= "<input type='submit' value='ok' style='display:none;'></form>";
        return $html . "<script>document.forms['alipaysubmit'].submit();</script>";
    }

    /**
     * 获取验证访问数据
     * @param array $options
     * @param string $method
     * @return array|bool
     * @throws PayException
     */
    protected function getResult($options, $method)
    {
        $this->config['biz_content'] = json_encode($options,JSON_UNESCAPED_UNICODE);
        $this->config['method'] = $method;
        $this->config['sign'] = $this->getSign();
        $method = str_replace('.', '_', $method) . '_response';
        $data = json_decode($this->post($this->gateway, $this->config), true);
        return $this->verify($data[$method], $data['sign'], true);
    }

    /**
     * 获取数据签名
     * @return string
     */
    protected function getSign()
    {
        if (is_null($this->userConfig['private_key'])) {
            throw new PayException('Missing Config -- [private_key]');
        }
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->userConfig['private_key'], 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($this->getSignContent($this->config), $sign, $res, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    /**
     * 数据签名处理
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param array $toBeSigned
     * @param bool $verify
     * @return bool|string
     */
    protected function getSignContent(array $toBeSigned, $verify = false)
    {
        ksort($toBeSigned);
        $stringToBeSigned = '';
        foreach ($toBeSigned as $k => $v) {
            if ($verify && $k != 'sign' && $k != 'sign_type') {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
            if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }
        $stringToBeSigned = substr($stringToBeSigned, 0, -1);
        unset($k, $v);
        return $stringToBeSigned;
    }
}
