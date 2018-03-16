<?php

  namespace angel;

  class wechat {

    private $appid;

    private $secret;

    private $token;

    private $re_text = [];

    private $re_event = [];

    private $from;

    public function __construct($Appid,$Secret,$Token){
      $this->appid = $Appid;
      $this->secret = $Secret;
      $this->token = $Token;
    }

    public function access_token(){
      $update_time = date('YmdHi');
      if(!file_exists(user::dir().'/file/json/access_token.json')){
        $request = curl::get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->secret);
        $at = json_decode($request)->access_token;
        $data = [
          'expire_time' => $update_time + 140,
          'access_token' => $at
        ];
        $fp = fopen(user::dir()."/file/json/access_token.json", "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      }else{
        $data = json_decode(file_get_contents(user::dir()."/file/json/access_token.json"));
        if ($data->expire_time < $update_time) {
          $request = curl::get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->secret);
          $at = json_decode($request)->access_token;
          $data = [
            'expire_time' => $update_time + 140,
            'access_token' => $at
          ];
          $fp = fopen(user::dir()."/file/json/access_token.json", "w");
          fwrite($fp, json_encode($data));
          fclose($fp);
        }else{
          $at = $data->access_token;
        }
      }
      return $at;
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
      $wx = \simplexml_load_string(file_get_contents('php://input'),'SimpleXMLElement',LIBXML_NOCDATA);
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
        call_user_func_array($list[$pattern],[$wx,$this]);
      }else{
        $flag = true;
        foreach($list as $key=>$value){
          if(is::in($key,$pattern)){
            call_user_func_array($list[$key],[$wx,$this]);
            $flag = false;
            break;
          }
        }
        if($flag){
          call_user_func_array($list['empty'],[$wx,$this]);
        }
      }
    }

    public function return($type,$return){
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
          $count = sizeof($return['articles']);
          echo '<xml>
          <ToUserName><![CDATA['.$return['to'].']]></ToUserName>
          <FromUserName><![CDATA['.$this->from.']]></FromUserName>
          <CreateTime>'.$time.'</CreateTime>
          <MsgType><![CDATA[news]]></MsgType>
          <ArticleCount>'.$count.'</ArticleCount>
          <Articles>';
          foreach($return['articles'] as $i){
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
        case 'transfer_customer_service':
          echo '<xml>
          <ToUserName>< ![CDATA['.$return['to'].'] ]></ToUserName>
          <FromUserName>< ![CDATA['.$this->from.'] ]></FromUserName>
          <CreateTime>'.$time.'</CreateTime>
          <MsgType>< ![CDATA[transfer_customer_service] ]></MsgType>
          <TransInfo>
            <KfAccount>< ![CDATA['.$return['id'].'] ]></KfAccount>
          </TransInfo>
          </xml>';
          break;
      }
    }

    public function tmp_return($at,$return){
      $post_json = json_encode([
        'touser' => $return['to'],
        'template_id' => $return['id'],
        'url' => $return['url'],
        'data' => $return['data']
      ],JSON_UNESCAPED_UNICODE);
      return curl::post('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$at,$post_json);
    }

    public function custom_return($at,$type,$return){
      $time = date('YmdHis');
      switch($type){
        case 'text':
          $post_json = json_encode([
            'touser' => $return['to'],
            'msgtype' => 'text',
            'text' => [
              'content' => $return['content']
            ]
          ],JSON_UNESCAPED_UNICODE);
          return curl::post('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$at,$post_json);
        break;
        case 'news':
          $post_json = json_encode([
            'touser' => $return['to'],
            'msgtype' => 'news',
            'news' => [
              'articles' => $return['articles']
            ]
          ],JSON_UNESCAPED_UNICODE);
          return curl::post('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$at,$post_json);
        break;
      }
    }

    public function menu($at, $menu){
      return curl::post('https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$at,json_encode(['button'=>$menu],JSON_UNESCAPED_UNICODE));
    } //重置菜单

    public function setup(){
      echo user::get('echostr');
    } //激活微信

    public static function get_qr_ticket($access_token,$in_p){
      $response = curl::post(
        'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token,
        '{"action_name":"QR_LIMIT_STR_SCENE","action_info":{"scene":{"scene_str":"'.$in_p.'"}}}'
      );
      $postObj = json_decode($response);
      return urlencode($postObj->ticket);
    }

    public static function get_tmp_qr_ticket($access_token,$in_p,$exp){
      $response = curl::post(
        'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token,
        '{"expire_seconds":'.$exp.',"action_name":"QR_STR_SCENE","action_info":{"scene":{"scene_str":"'.$in_p.'"}}}'
      );
      $postObj = json_decode($response);
      return urlencode($postObj->ticket);
    }

    public static function get_qr_img($ticket){
      return 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
    }

    public function get_user_info($at,$openid){
      return json_decode(curl::get('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$at.'&openid='.$openid.'&lang=zh_CN'));
    }

    public function get_all_user_id($at){
      $out = json_decode(curl::get('https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$at));
      $raw = $out->data->openid;
      if((int)$out->count<(int)$out->total){
        $next = 1;
        while((int)$out->count<(int)$out->total){
          $to = json_decode(curl::get('https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$at.'&next_openid=NEXT_OPENID'.$next));
          $raw = ary::merge([$raw,$to->data->openid]);
          $out->count = $out->count+$to->count;
          $next = $next+1;
        }
      }
      return $raw;
    }

    public function jsapi($at){
      $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
      $str = "";
      for ($i = 0; $i < 16; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
      }
      if(!file_exists(user::dir().'/file/json/jsapi_ticket.json')){
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token='.$at;
        $res = json_decode(curl::get($url));
        $ticket = $res->ticket;
        if($ticket){
          $data = [
            'expire_time' => time() + 7000,
            'jsapi_ticket' => $ticket
          ];
          $fp = fopen(user::dir()."/file/json/jsapi_ticket.json", "w");
          fwrite($fp, json_encode($data));
          fclose($fp);
        }
      }else{
        $data = json_decode(file_get_contents(user::dir()."/file/json/jsapi_ticket.json"));
        if ($data->expire_time < time()) {
          $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token='.$at;
          $res = json_decode(curl::get($url));
          $ticket = $res->ticket;
          if($ticket){
            $data->expire_time = time() + 7000;
            $data->jsapi_ticket = $ticket;
            $fp = fopen(user::dir()."/file/json/jsapi_ticket.json", "w");
            fwrite($fp, json_encode($data));
            fclose($fp);
          }
        }else{
          $ticket = $data->jsapi_ticket;
        }
      }
      $timestamp = time();
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
      $string = "jsapi_ticket=$ticket&noncestr=$str&timestamp=$timestamp&url=$url";
      $sign = sha1($string);
      $signPackage = array(
        "appid"     => $this->appid,
        "noncestr"  => $str,
        "timestamp" => $timestamp,
        "signature" => $sign,
        "rawString" => $string
      );
      return $signPackage;
    }

  }
