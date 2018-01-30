<?php
/**
 * 支付模块
 */
namespace ixiaomu\payment;

use ixiaomu\payment\exceptions\PayException;
use ixiaomu\payment\libs\Config;

class Pay extends \yii\base\Component
{
    private $config; //支付配置

    private $drivers; //支付方式

    private $gateways; //支付通道

    public function __construct(array $config = [])
    {
        if (empty($config)){
            throw new PayException("Payment Config is not defined.");
        }
        $this->config = $config;
    }

    public function driver($driver)
    {
        if (is_null($driver)) {
            throw new PayException("Driver [$driver] is not defined.");
        }
        $this->drivers = $driver;
        return $this;
    }

    /**
     * 指定操作网关
     * @param string $gateway
     * @return GatewayInterface
     */
    public function gateway($gateway = 'mp')
    {
        if (!isset($this->drivers)) {
            throw new PayException('Driver is not defined.');
        }
        return $this->gateways = $this->createGateway($gateway);
    }

    /**
     * 创建操作网关
     * @param string $gateway
     * @return mixed
     */
    protected function createGateway($gateway)
    {
        if (!file_exists(__DIR__ . '/gateways/' . strtolower($this->drivers) . '/' . strtolower($gateway) . 'Pay.php')) {
            throw new PayException("Gateway [$gateway] is not supported.");
        }
        $gateway = __NAMESPACE__ . '\\gateways\\' . strtolower($this->drivers) . '\\' . strtolower($gateway) . 'Pay';
        return new $gateway($this->config);
    }


}
