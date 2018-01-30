支付组件
====
YII2 支付组件（支付宝支付、微信支付）

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist ixiaomu/yii2-payment "*"
```

or add

```
"ixiaomu/yii2-payment": "*"
```

to the require section of your `composer.json` file.


Used || Example
---------------
//根据不同的支付方式及通道 传入不同的支付配置及支付数据
```php
<?= 
    use ixiaomu\payment\Pay;

    $payConfig = [ //换成自己的
        'app_id'        => 'wx69f7d561891b969d',  // 公众账号ID
        'mch_id'        => '1597082962',// 商户id
        'mch_key'           => '1n156wnp3qtihtht93y2xte7bxeoz2ub',// 秘钥
        'app_secret'    => '9e261fd174495ef81989daebfdcd9c05',
        'fee_type'      => 'CNY',// 货币类型  当前仅支持该字段
        'sslcert_path'  => Yii::getAlias('@common').'/lib/payment/wechat/cert/apiclient_cert.pem',
        'sslkey_path'   => Yii::getAlias('@common').'/lib/payment/wechat/cert/apiclient_key.pem',
        'notify_url'    => Yii::getAlias('@apiUrl').'/RestApi/v1/wechat-callback/wx-notify',
    ];
    
    $payData = [
         'out_trade_no'     => '12345678901', // 订单号
         'total_fee'        => '520000', // 订单金额，**单位：分**
         'body'             => '订单描述', // 订单描述
         'openid'           => 'o89PEv2vT_niCY9n5nNAsQWK4D_Q', // 支付人的 openID
    ];
    $pay = new Pay($payConfig);
    try{
        $redult = $pay->driver('wechat')->gateway('mp')->apply($payData);
        var_dump($redult);die;
    }catch (Exception $e){
        throw new Exception('支付失败：'.$e->getMessage());
    }
    
```