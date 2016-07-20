<?php
/**
 * Created by PhpStorm.
 * User: yzy
 * Date: 2016/5/16
 * Time: 13:48
 */
if(!defined("REDISCONFIG_HOST"))
    define('REDISCONFIG_HOST', "127.0.0.1");
//redis port
if(!defined("REDISCONFIG_PORT"))
    define('REDISCONFIG_PORT', "6379");
//redis 过期时间
if(!defined("REDISTIMES"))
    define('REDISTIMES', 1888);
//AppID
if(!defined("COMPONENT_APPID"))
define('COMPONENT_APPID', "xxxxxxx");
//AppSecret
if(!defined("COMPONENT_APPSECRET"))
define('COMPONENT_APPSECRET', "xxxxxxxxxxx");
//TOKEN
define('SQ_TOKEN', "ZDYTOKENS");
//公众号消息加解密Key
define('SQ_ENCODINGAESKEY', "qwertyuioplkjhgfdsamnbvcxzqwertyuioplkjhgfd");
//获取第三方平台component_access_token URL
define('COMPONENT_ACCESS_TOKEN_POST_URL', "https://api.weixin.qq.com/cgi-bin/component/api_component_token");
//3、获取预授权码pre_auth_code   URL
define('SQ_PRE_AUTH_CODE_URL', "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=");
//4、使用授权码换取公众号的接口调用凭据和授权信息  URL
define('SQ_AUTHORIZATION_CODE_URL', "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=");
//微信跳转至 授权页连接 在createLink方法内使用
//https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=".COMPONENT_APPID."&pre_auth_code={$pre}&redirect_uri=
//5、获取（刷新）授权公众号的接口调用凭据（令牌）
define('SQ_REAUTHORIZATION_CODE_URL', "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=");
//6、获取授权方的公众号帐号基本信息
define('SQ_MPUSERINFO_CODE_URL', "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=");
//7、获取授权方的选项设置信息
define('SQ_MPUSERINFOROOT_CODE_URL', "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option?component_access_token=");
//创建自定义菜单
define("SQ_CREATE_MENU_POST_URL","https://api.weixin.qq.com/cgi-bin/menu/create?access_token=");
//查询自定义菜单
define("SQ_SELECT_MENU_POST_URL","https://api.weixin.qq.com/cgi-bin/menu/get?access_token=");
//删除自定义菜单
define("SQ_DELECT_MENU_POST_URL","https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=");
include_once __DIR__."/wxBizMsgCrypt.php";
class BaseWxYJSQ {
    private  $_wx_url = array(
        //获取access_token的URL地址
        'get_token' => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s',
        //上传多媒体文件URL
        'upload_media' => 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=%s&type=%s',
        //获取用户基本信息
        'get_userinfo' => 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN',
        //获取网页授权code
        'get_code' => 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=STATE&component_appid=%s#wechat_redirect',
        //通过code换取网页授权access_token
        'get_access_token' => 'https://api.weixin.qq.com/sns/oauth2/component/access_token?appid=%s&code=%s&grant_type=authorization_code&component_appid=%s&component_access_token=%s',
        //刷新网页授权access_token
        'get_refresh_token' => 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=%s&grant_type=refresh_token&refresh_token=%s',
        //网页授权拉取用户信息
        'get_auth_userinfo' => 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN',
        //发送客服消息
        'send_message' => 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=',
        //创建二维码ticket
        'create_ticket' => 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=',
        //通过ticket换取二维码
        'showqrcode' => 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=',
        //下载媒体文件
        'loadmedia' => 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=%s&media_id=%s',
        //模板消息接口:发送模板消息
        'template' => 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=',
        //模板消息接口:设置所属行业
        'set_industry' => 'https://api.weixin.qq.com/cgi-bin/template/api_set_industry?access_token=',
        //模板消息接口:获得模板ID
        'add_template' => 'https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token='
    );


    /**
     * 微信每10分钟推送ComponentVerifyTicketBase 底层类  获取方法 by yzy
     * @param $getdata
     * @param $code
     * @return int|string
     */
    function getComponentVerifyTicketBase($getdata=null,$code=null){
        $token = SQ_TOKEN;
        $appId = COMPONENT_APPID;
        $encodingAesKey = SQ_ENCODINGAESKEY;
        $pc = new WXBizMsgCrypt($token, $encodingAesKey, $appId);
        $msg = '';
        $timeStamp = $getdata['timestamp'];
        $nonce = $getdata['nonce'];
        $msg_sign = $getdata['msg_signature'];
        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $code);
        $err = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        if($err==0){
            //简易解密重新负值
            $GLOBALS ["HTTP_RAW_POST_DATA"]=$msg;
            $vals = $this->xmlToArr($msg);
            return $vals;
        } else{
            return $err;
        }
    }
    /**
     * 获取第三方平台component_access_tokenBase
     * @param $vals //component_verify_ticket
     * @return array|mixed|stdClass
     */
    function getComponentAccessTokenBase($vals=null){
        $url = COMPONENT_ACCESS_TOKEN_POST_URL;
        $post_data = array(
            "component_appid" => COMPONENT_APPID,
            "component_appsecret" => COMPONENT_APPSECRET,
            "component_verify_ticket" => $vals
        );
        $post_data = json_encode($post_data);
        $component_access_tokendata = $this->curls($url, $post_data);
        return json_decode($component_access_tokendata,true);
    }
    /**
     * 获取预授权码pre_auth_code
     * @return bool|string
     */
    function getPreAuthCodeBase(){
        $url = SQ_PRE_AUTH_CODE_URL;
        $url = $url . $this->getComponentAccessToken();
        $post_data = array("component_appid" => COMPONENT_APPID);
        $post_data = json_encode($post_data);
        return json_decode($this->curls($url, $post_data),true);
    }

    /**
     * @param null $auth_code
     * @return null
     *
    authorization_info	        授权信息
    authorizer_appid	        授权方appid
    authorizer_access_token	    授权方接口调用凭据（在授权的公众号具备API权限时，才有此返回值），也简称为令牌
    expires_in	                有效期（在授权的公众号具备API权限时，才有此返回值）
    authorizer_refresh_token	接口调用凭据刷新令牌（在授权的公众号具备API权限时，才有此返回值），
    刷新令牌主要用于公众号第三方平台获取和刷新已授权用户的access_token，只会在授权时刻提供，
    请妥善保存。 一旦丢失，只能让用户重新授权，才能再次拿到新的刷新令牌
    func_info	                公众号授权给开发者的权限集列表，ID为1到15时分别代表：
    消息管理权限
    用户管理权限
    帐号服务权限
    网页服务权限
    微信小店权限
    微信多客服权限
    群发与通知权限
    微信卡券权限
    微信扫一扫权限
    微信连WIFI权限
    素材管理权限
    微信摇周边权限
    微信门店权限
    微信支付权限
    自定义菜单权限
    请注意：
    1）该字段的返回不会考虑公众号是否具备该权限集的权限（因为可能部分具备），请根据公众号的帐号类型和认证情况，来判断公众号的接口权限。
     */
    function  getUserInfo($auth_code=null){
        empty($auth_code)&&$auth_code=$_GET["auth_code"];
        if(empty($auth_code)) return null;
        $url = SQ_AUTHORIZATION_CODE_URL;
        $url = $url . $this->getComponentAccessToken();
        $post_data = array(
            "component_appid" => COMPONENT_APPID,
            "authorization_code" => $auth_code
        );
        $post_data = json_encode($post_data);
        return json_decode($this->curls($url, $post_data),true);

    }

    /**
     * 刷新公众号authorizer_access
     * @param  $appid  $reftok
     * @return array|mixed|false|stdClass
     */
    function  getReFtok($appid=null,$reftok=null){

        if(empty($appid)){return false;}
        if(empty($reftok)){return false;}
        $url = SQ_REAUTHORIZATION_CODE_URL;
        $url = $url . $this->getComponentAccessToken();
        $post_data = array(
            "component_appid" => COMPONENT_APPID,
            "authorizer_appid" => $appid,
            "authorizer_refresh_token" => $reftok
        );

        $post_data = json_encode($post_data);
        $rs=$this->curls($url, $post_data);
        return json_decode($rs,true);
    }



    /**
     * 6、获取授权方的公众号帐号基本信息
     * @param $component_appid
     * @param $authorizer_appid
     * @return array|bool|mixed|stdClass
     */
    function getMPUserInfo($component_appid=null,$authorizer_appid=null){
        if(empty($component_appid))return false;
        if(empty($authorizer_appid))return false;
        $url = SQ_MPUSERINFO_CODE_URL;
        $url = $url . $this->getComponentAccessToken();
        $post_data = array(
            "component_appid" => $component_appid,
            "authorizer_appid" => $authorizer_appid
        );
        $post_data = json_encode($post_data);
        return json_decode($this->curls($url, $post_data),true);
    }
    /**
     * 7、获取授权方的选项设置信息
     * @param $component_appid
     * @param $authorizer_appid
     * @return array|bool|mixed|stdClass
     *
     * option_name	option_value	选项值说明
    location_report(地理位置上报选项)	0	无上报
    1	进入会话时上报
    2	每5s上报
    voice_recognize（语音识别开关选项）	0	关闭语音识别
    1	开启语音识别
    customer_service（多客服开关选项）	0	关闭多客服
    1	开启多客服
     */
    function getMPUserInfoRoot($component_appid=null,$option_name=null){
        if(empty($component_appid))return false;
        if(empty($authorizer_appid))return false;
        $url = SQ_MPUSERINFOROOT_CODE_URL;
        $url = $url . $this->getComponentAccessToken();
        $post_data = array(
            "component_appid" => COMPONENT_APPID,
            "authorizer_appid" => $component_appid,
            "option_name" => $option_name
        );

        $post_data = json_encode($post_data);
        return json_decode($this->curls($url, $post_data),true);
    }

// --------------------用户授权模块---------------------------   //
    /**
     * 获取 用户openid
     */
    function getAuthUserInfo($appid=null,$redi_url=null){

        $code=$this->getCode($appid,$redi_url);
        $open=$this->getAccessToken($appid,$code);
        $user=$this->userInfo($open['access_token'],$open['openid']);
        return $user;
    }

    /**
     * 网页授权
     * @param null $appid
     * @param null $redi_url
     * @param null $scope
     * @return bool
     */
    function getCode($appid=null,$redi_url=null){
        if(empty($appid)) return false;
        if(empty($redi_url)) return false;
        $redi_url=urlencode ($redi_url);
        if(empty($_GET['code'])) {
            $component_appid=COMPONENT_APPID;
            $url = sprintf($this->_wx_url['get_code'],$appid,$redi_url,"snsapi_userinfo",$component_appid);
            header("location:".$url);
        }else{
            return $_GET['code'];
        }
    }

    /**
     * 获取acctok 网页授权accesstok 并不是  基础accesstok
     * @param null $appid
     * @param null $code
     * @return array|bool|mixed|stdClass
     */
    function getAccessToken($appid=null,$code=null){
        if(empty($code))return false;
        if(empty($appid))return false;
        $component_appid=COMPONENT_APPID;
        $component_access_token=$this->getComponentAccessToken();
        $url = sprintf($this->_wx_url['get_access_token'],$appid,$code,$component_appid,$component_access_token);
        return json_decode($this->curls($url,null),true);
    }

    /**
     * 刷新acctok
     * @param null $ref
     * @param null $appid
     * @return array|bool|mixed|stdClass
     */
    function refreshToken($ref=null,$appid=null){
        if(empty($ref))return false;
        if(empty($appid))return false;
        $url = sprintf($this->_wx_url['get_refresh_token'],$appid,$ref);
        return json_decode($this->curls($url,null),true);
    }

    /**
     * 抽取用户信息
     * @param null $access_token
     * @param null $openid
     * @return array|bool|mixed|stdClass
     */
    function userInfo($access_token=null,$openid=null){
        if(empty($access_token))return false;
        if(empty($openid))return false;
        $url = sprintf($this->_wx_url['get_auth_userinfo'],$access_token,$openid);
        return json_decode($this->curls($url,null),true);
    }
    /**
     * 微信xml加密
     * @param null $mxl
     * @return int|string
     */
    function enCodeWX($mxl=null){
        $encodingAesKey = SQ_ENCODINGAESKEY;
        $token = SQ_TOKEN;
        $timeStamp =  time();
        $nonce ="ZDYNONCE";
        $appId = COMPONENT_APPID;
        $text = $mxl;
        $pc = new WXBizMsgCrypt($token, $encodingAesKey, $appId);
        $encryptMsg = '';
        $errCode = $pc->encryptMsg($text, $timeStamp, $nonce, $encryptMsg);
        if ($errCode == 0) {
            return $encryptMsg;
        } else {
            return $errCode ;
        }
    }

//    ------------------工具模块------------------------------   //

    /**
     * 简易curl
     * @param null $url
     * @param null $data
     * @return mixed|null
     */
    function curls($url = null, $data = null)
    {
        if (empty($url)) return null;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        return ($output);
    }

    /**
     * 将XML 转换成 array
     * @param $xml
     * @return mixed
     */
    function  xmlToArr($xml=null)
    {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        return $vals;
    }
    function pdoTool(){
        $path=dirname(dirname(dirname(__FILE__)));
        include $path."/public/config/global.config.php";
        try {
            $pdo = new \PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PWD);
            $pdo->exec('SET CHARACTER SET '.DB_CHART);
            $pdo->exec('SET NAMES '.DB_CHART);
            return $pdo;
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    function array2Json($data=null) {
        static $format_func = null;
        if ($format_func === null) {
            $format_func = create_function ( '&$value', '
			if(is_bool($value)) {
            	$value = $value?\'true\':\'false\';
	        }elseif(is_int($value)) {
	            $value = intval($value);
	        }elseif(is_float($value)) {
	            $value = floatval($value);
	        }elseif(defined($value) && $value === null) {
	            $value = strval(constant($value));
	        }elseif(is_string($value)) {
	            $value = \'"\'.addslashes($value).\'"\';
	        }
	        return $value;
		' );
        }
        if (is_object ( $data )) {
            $data = get_object_vars ( $data );
        } else if (! is_array ( $data )) {
            return $format_func ( $data );
        }
        if (empty ( $data ) || is_numeric ( implode ( '', array_keys ( $data ) ) )) {
            $assoc = false;
        } else {
            $assoc = true;
        }
        $json = $assoc ? '{' : '[';
        foreach ( $data as $key => $val ) {
            if (! is_null ( $val )) {
                if ($assoc) {
                    $json .= "\"$key\":" . array2Json ( $val ) . ",";
                } else {
                    $json .= array2Json ( $val ) . ",";
                }
            }
        }
        if (strlen ( $json ) > 1) {
            $json = substr ( $json, 0, - 1 );
        }
        $json .= $assoc ? '}' : ']';
        return $json;
    }
}