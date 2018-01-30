<?php
/**
 * microPay.php.
 * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/29 17:50
 * Desctiption:
 */

namespace ixiaomu\payment\gateways\wechat;
use ixiaomu\payment\exceptions\PayException;
use ixiaomu\payment\gateways\Wechat;

/**
 * 刷卡支付调用示例
 * $payData = [
 *    'out_trade_no'     => '3412', // 订单号
 *    'total_fee'        => '1', // 订单金额， 单位：分
 *    'body'             => '', // 订单描述
 *    'auth_code'        => '', // 授权码
 *
 * ];
 * $pay = new Pay($payConfig);
 * $result = $pay->drive('wechat')->gateway('micro')->apply($payData);
 */
class microPay extends Wechat
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
        unset($this->config['trade_type']);
        unset($this->config['notify_url']);
        $this->config = array_merge($this->config, $options);
        $this->config['sign'] = $this->getSign($this->config);
        $data = $this->fromXml($this->post($this->url_micropay, $this->toXml($this->config)));
        if (isset($data['err_code']) && $data['err_code'] === 'USERPAYING'){
            return 'USERPAYING';  //需要用户输入支付密码
        }
        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS' || $data['result_code'] !== 'SUCCESS') {
            $error = 'WeChatError:' . $data['return_msg'];
            $error .= isset($data['err_code_des']) ? ' - ' . $data['err_code_des'] : '';
        }
        if(isset($data['sign'])) {
            if (!isset($error) && $this->getSign($data) !== $data['sign']) {
                $error = 'WeChatError: return data sign error';
            }
        }
        if (isset($error)) {
            throw new PayException($error);
        }
        return $data;
    }
}