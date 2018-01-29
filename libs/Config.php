<?php
/**
 * Config.php.
 * Author: MYL <ixiaomu@qq.com>
 * Date: 2018/1/29 10:50
 * Desctiption: 支付配置对象
 */
namespace ixiaomu\payment\lib;

use ArrayAccess;
use ixiaomu\payment\exceptions\PayException;

class Config implements ArrayAccess
{
    protected $config;

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    /**
     * get
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param null $key
     * @param null $default
     * @return array|mixed|null
     */
    public function get($key = null, $default = null){
        $config = $this->config;
        if (is_null($key)) {
            return $config;
        }
        if (isset($config[$key])) {
            return $config[$key];
        }
        foreach (explode('.', $key) as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }
        return $config;
    }

    /**
     * set
     * Author : MYL <ixiaomu@qq.com>
     * Updater：
     * @param $key
     * @param $value
     * @return array
     */
    public function set($key, $value)
    {
        if ($key == '') {
            throw new PayException('Invalid config key.');
        }
        // 只支持三维数组，多余无意义
        $keys = explode('.', $key);
        switch (count($keys)) {
            case '1':
                $this->config[$key] = $value;
                break;
            case '2':
                $this->config[$keys[0]][$keys[1]] = $value;
                break;
            case '3':
                $this->config[$keys[0]][$keys[1]][$keys[2]] = $value;
                break;
            default:
                throw new PayException('Invalid config key.');
        }
        return $this->config;
    }

    //判断是否有配置
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->config);
    }

    //获取配置
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    //设置配置
    public function offsetSet($offset, $value)
    {
        return $this->set($offset,$value);
    }

    //清除配置
    public function offsetUnset($offset)
    {
        $this->set($offset, null);
    }
}