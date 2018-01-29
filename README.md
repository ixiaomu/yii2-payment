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


Usage
-----

Once the extension is installed, simply use it in your code by  :
```php
<?= \ixiaomu\payment\AutoloadExample::widget(); ?>

```
Example
-----------
//根据不同的支付方式及通道 传入不同的支付配置及支付数据
```php
<?= 
    

    $payConfig = [
      'app_id' => '2313216565498511321' ,
      ......
    ];
    
    $payData = [
         'out_trade_no'     => '58234123', // 订单号
         'total_fee'        => '1000521', // 订单金额，**单位：分**
         'body'             => '订单描述', // 订单描述
         'spbill_create_ip' => '127.0.0.1', // 支付人的 IP
         'openid'           => 'ol0Q_uJUcrb1DOjmQRycmSpLjRmo', // 支付人的 openID
    ];
    $pay = new \Pay\Pay($payConfig);
    try{
        $redult = $pay->driver('Wx')->gateway('mp')->apply($payData);
        var_dump($redult);die;
    }catch (Exception $e){
        throw new Exception('支付失败：'.$e->getMessage());
    }
    
```