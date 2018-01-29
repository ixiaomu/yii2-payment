<?php
namespace ixiaomu\payment;


use ixiaomu\payment\exceptions\PayException;
use ixiaomu\payment\lib\Config;

class Pay extends \yii\base\Widget
{
    private $config; //支付配置

    private $drivers; //支付方式

    private $gateways; //支付通道

    public function __construct(array $config = [])
    {
        if (!empty($config)){
            $this->config = $config;
        }
        throw new PayException('请先设置支付配置！');
    }

    public function driver($driver)
    {
        if (is_null($this->config->get($driver))) {
            throw new PayException("Driver [$driver]'s Config is not defined.");
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
        if (!file_exists(__DIR__ . '/gateways/' . ucfirst($this->drivers) . '/' . ucfirst($gateway) . 'Pay.php')) {
            throw new PayException("Gateway [$gateway] is not supported.");
        }
        $gateway = __NAMESPACE__ . '\\Gateways\\' . ucfirst($this->drivers) . '\\' . ucfirst($gateway) . 'Gateway';
        return new $gateway($this->config->get($this->drivers));
    }


}
