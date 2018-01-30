<?php
/**
 * transferPay.php.
 * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/29 17:54
 * Desctiption:  企业付款  （提现）
 */

namespace ixiaomu\payment\gateways\wechat;


use ixiaomu\payment\exceptions\PayException;
use ixiaomu\payment\gateways\Wechat;

class transferPay extends Wechat
{
    /**
     * 应用并返回数据
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param array $options
     * @return mixed
     * @throws PayException
     */
    public function apply(array $options = []){
        $options['mchid'] = $this->config['mch_id'];
        $options['mch_appid'] = $this->userConfig['app_id'];
        unset($this->config['appid']);
        unset($this->config['mch_id']);
        unset($this->config['sign_type']);
        unset($this->config['trade_type']);
        unset($this->config['notify_url']);
        $this->config = array_merge($this->config, $options);
        $this->config['sign'] = $this->getSign($this->config);
        $data = $this->fromXml($this->post(
            $this->gateway_transfer, $this->toXml($this->config),
            ['ssl_cer' => $this->userConfig['sslcert_path'], 'ssl_key' => $this->userConfig['sslkey_path']]
        ));
        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS' || $data['result_code'] !== 'SUCCESS') {
            $error = 'WeChatError:' . $data['return_msg'];
            $error .= isset($data['err_code_des']) ? ' - ' . $data['err_code_des'] : '';
        }
        if (isset($error)) {
            throw new PayException($error, 20000);
        }
        return $data;
    }
}