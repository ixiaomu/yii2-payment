<?php
/**
 * barPay.php.
 * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/30 17:53
 * Desctiption:
 */
namespace ixiaomu\payment\gateways\alipay;

use ixiaomu\payment\exceptions\PayException;
use ixiaomu\payment\gateways\Alipay;

class barPay extends Alipay
{
    /**
     * 应用并返回数据
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param array $options
     */
    public function apply(array $options = [], $scene = 'bar_code'){
        $options['scene'] = $scene;
        $this->config['biz_content'] = json_encode($options,JSON_UNESCAPED_UNICODE);
        $this->config['method'] = $this->getMethod();
        $this->config['sign'] = $this->getSign();
        $method = str_replace('.', '_', $this->config['method']) . '_response';
        $data = json_decode($this->post($this->gateway, $this->config), true);
        return $this->verify($data[$method], $data['sign'], true);
    }

    /**
     * 接口名称
     * @return string
     */
    public function getMethod()
    {
        return 'alipay.trade.pay';
    }

    /**
     * 销售产品码
     * @return string
     */
    public function getProductCode()
    {
        return 'FACE_TO_FACE_PAYMENT';
    }
}