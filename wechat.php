<?php

  namespace angel;

  class wechat {

    private $appid;

    private $secret;

    private $token;

    private $re_text;

    private $re_event;

    private $from;

    public function __construct($Appid,$Secret,$Token){
      $this->appid = $Appid;
      $this->secret = $Secret;
      $this->token = $Token;
    }

    public function access_token(){
      $at = sql::select('access_token')->where('ID=?',[$this->appid])->limit(1)->fetch();
      $update_time = date('YmdHi');
      if(($update_time-$at['Updete_Time'])<139){
        return $at['Token'];
      }else {
        $request = curl::get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret);
        $at = json_decode($request)->access_token;
        sql::update('access_token')->this([
          'Token'=>$at,
          'Updete_Time'=>$update_time
        ])->where('ID=?',[$this->appid])->execute();
        return $at;
      }
    } //access_token

    public function listen($kind,$pattern,$method){
      switch($kind) {
        case 'text':
          $list =& $this->re_text;
          break;
        case 'event':
          $list =& $this->re_event;
          break;
      }
      if(!array_key_exists($pattern,$list)) {
        $list[$pattern] = $method;
      }
    }

    public function run(){
      $wx = simplexml_load_string(file_get_contents('php://input'),'SimpleXMLElement',LIBXML_NOCDATA);
      session_start();
      $_SESSION['openid'] = $wx->FromUserName;
      switch(trim($wx->MsgType)){
        case 'text':
          $pattern = trim($wx->Content);
          $list =& $this->re_text;
          break;
        case 'event':
          $list =& $this->re_event;
          $pattern = trim($wx->Event);
          break;
      }
      $this->from = $wx->ToUserName;
      if(array_key_exists($pattern,$list)) {
        call_user_func_array($list[$pattern],[$wx]);
      }
    }

    public static function return($type,$return){
      $time = date('YmdHis');
      switch($type){
        case 'text':
          echo '<xml>
          <ToUserName><![CDATA['.$return['to'].']]></ToUserName>
          <FromUserName><![CDATA['.$this->from.']]></FromUserName>
          <CreateTime>'.$time.'</CreateTime>
          <MsgType><![CDATA[text]]></MsgType>
          <Content><![CDATA['.$return['content'].']]></Content>
          <FuncFlag>0</FuncFlag>
          </xml>';
          break;
        case 'news':
          $count = sizeof($return)-1;
          echo '<xml>
          <ToUserName><![CDATA['.$return['to'].']]></ToUserName>
          <FromUserName><![CDATA['.$this->from.']]></FromUserName>
          <CreateTime>'.$time.'</CreateTime>
          <MsgType><![CDATA[news]]></MsgType>
          <ArticleCount>'.$count.'</ArticleCount>
          <Articles>';
          foreach($return as $i){
            if(is_array($i)){
              echo '<item>
              <Title><![CDATA['.$i['title'].']]></Title>
              <Description><![CDATA['.$i['description'].']]></Description>
              <PicUrl><![CDATA['.$i['picurl'].']]></PicUrl>
              <Url><![CDATA['.$i['url'].']]></Url>
              </item>';
            }
          }
          echo '</Articles>
          </xml>';
          break;
      }
    }

    public function menu($at, $menu){
      return curl::post('https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$at,json_encode(['button'=>$menu],JSON_UNESCAPED_UNICODE));
    } //重置菜单

    public function setup(){
      echo user::get('echostr');
    } //激活微信
  }
