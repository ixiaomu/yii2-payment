<?php
/**
 * barPay.php * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/30 9:24
 * Desctiption:
 */

namespace ixiaomu\payment\alipay;

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
        return $this->getResult($options, $this->getMethod());
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