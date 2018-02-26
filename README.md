<p align="center"><img width="320" src="https://xy.zuggr.com/file/angel_wechat.jpg"></p>

中文档案
-------------
**Angel微信** 是基于[**Angel框架**](https://github.com/angel-project/framework)的公众号开发框架

安装
-------------
请先使用Composer安装[**Angel框架**](https://github.com/angel-project/framework):
```
composer create-project angel-project/framework .
```

然后安装微信拓展:
```
composer require angel-project/wechat
```

![GitHub php](https://img.shields.io/packagist/php-v/symfony/symfony.svg)
![GitHub license](https://img.shields.io/cocoapods/l/AFNetworking.svg)  

公众号接收/反馈
-------------
以下是一个基本/反馈监听模板：
```PHP
  build::post('your/url',function(){

    $wx = new angel\wechat($appid,$secret,$token); //初始化微信object

    $wx->listen('text','hi',function($input,$wx){
      $wx->return('text',[
        'to' => $input->FromUserName,
        'content' => 'hello!'
      ]);
    }); //监听文本，返回字符串hello!

    $wx->run(); //执行监听

  });
```

English Doc
-------------
**Angel WeChat** is a WeChat plugin based on [**Angel Framework**](https://github.com/angel-project/framework)  

Installation
-------------
First, **install** [**Angel Framework**](https://github.com/angel-project/framework) with Composer using the following command:
```
composer create-project angel-project/framework .
```

Then, **install** Angel WeChat package:
```
composer require angel-project/wechat
```

![GitHub php](https://img.shields.io/packagist/php-v/symfony/symfony.svg)
![GitHub license](https://img.shields.io/cocoapods/l/AFNetworking.svg)
