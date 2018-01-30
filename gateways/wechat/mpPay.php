<?php
/**
 * mpPay.php.
 * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/29 16:15
 * Desctiption:
 */

namespace ixiaomu\payment\gateways\wechat;


use ixiaomu\payment\gateways\Wechat;

class mpPay extends Wechat
{
    /**
     * 应用并返回数据
     * @param array $options
     * @return array
     * @throws PayException
     */
    public function apply(array $options = []){
        $options['trade_type'] = 'JSAPI';
        $result = $this->preOrder($options);
        $payRequest = [
            'appId'     => $this->userConfig['app_id'],
            'timeStamp' => strval(time()),
            'nonceStr'  => strval($this->createNonceStr()),
            'package'   => 'prepay_id=' . $result['prepay_id'],
            'signType'  => 'MD5',
        ];
        $payRequest['paySign'] = $this->getSign($payRequest,$this->userConfig['mch_key']);
        return $payRequest;
    }
}