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

公众号开发
-------------
一个基本的监听/反馈模板：
```PHP
  build::post('your/url',function(){

    $wx = new angel\wechat($appid,$secret,$token); //初始化微信object

    $wx->listen('text','hi',function($input,$wx){
      $wx->return('text',[
        'to' => $input->FromUserName,
        'content' => 'hello!'
      ]);
    }); //当用户输入hi时，返回字符串hello!

    $wx->listen('event','subscribe',function($input,$wx){
      $wx->return('news',[
        'to' => $input->FromUserName,
        'articles' => [[
          'title' => 'hi!',
          'description' => 'long time no see',
          'picurl' => 'yoururl.com/img.jpg',
          'url' => 'yoururl.com'
        ]]
      ]); //返回主题
    }); //当用户关注时触发，返回图文消息

    $wx->run(); //执行监听

  });
```
listen()方法有三个输入：
- 监听方法：目前支持text（文字输入）、event（事件触发）
- 触发事件：文字支持字符串和正则表达式匹配，事件支持SCAN（扫码）、subscribe（关注）、CLICK（点击）触发。当用户输入不满足任何规则时，当触发事件为'empty'。
- 触发后执行的代码：该function输入两个值：用户输入和微信object。你可以通过用户输入来获取所有用户传送至服务器的指令；微信object让你在function继续自由使用微信方法。


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
