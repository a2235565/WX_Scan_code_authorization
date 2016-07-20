<?php
/**
 * Created by yzy.
 * User: yzy
 * Date: 2016/5/3
 * Time: 11:20
 * 基于redis缓存 若没安装缓存请自行修改成其他缓存介质
 */
include_once __DIR__."/../config/global.config.php";
include_once "BaseWxYJSQ.php";
class WeiXinSQ  extends BaseWxYJSQ{
    /**
     * 回调事件接受口
     * @return bool
     */
    function callBack(){
        //全网发布开启哦
//        $this->wxRelease();
//        die();
        if(empty($GLOBALS ["HTTP_RAW_POST_DATA"])) return false;
        $xml = $GLOBALS ["HTTP_RAW_POST_DATA"];
        $vals= $this->xmlToArr($xml);
        $get=$_GET;

        if(!empty($get['sq'])){
            $power= explode("/",$get['sq']);
        }
        if(empty($power[1])){
        //推送ComponentVerifyTicket事件
            if(!empty($vals[3]['tag'])&&$vals[3]['tag']==="ENCRYPT"){
                $this->getComponentVerifyTicket();
            }
        }else{
            //公众号事件
            if(!empty($vals[3]['tag'])&&$vals[3]['tag']==="ENCRYPT"){
                $this->goCallBack();
                die();
            }
        }
        //第三方平台方在收到授权相关通知后也需进行解密
        //（详细请见【消息加解密接入指引】），
        //接收到后之后只需直接返回字符串success。
        //为了加强安全性，postdata中的xml将使用服务申请时的加解密key来进行加密，具体请见
        //POST数据示例（授权成功通知）
        if(!empty($vals[5]['value'])&&$vals[5]['value']==="authorized")
        {
            echo "success";
            die();
        }
        //POST数据示例（取消授权通知）
        if(!empty($vals[5]['value'])&&$vals[5]['value']==="unauthorized")
        {
            echo "success";
            die();
        }
        //POST数据示例（授权更新通知）
        if(!empty($vals[5]['value'])&&$vals[5]['value']==="updateauthorized")
        {
            echo "success";
            die();
        }
    }
    /**
     * 公众号回调事件
     */
    function goCallBack(){
        if(!empty( $GLOBALS ["HTTP_RAW_POST_DATA"])) {
//           解密
            $datasget = $_GET;
            include_once "wxBizMsgCrypt.php";
            $xml = $GLOBALS ["HTTP_RAW_POST_DATA"];
            $vals = $this->xmlToArr($xml);
//          $GLOBALS ["HTTP_RAW_POST_DATA"]解密且重新负值
            $data=$this->getComponentVerifyTicketBase($datasget, $vals[3]['value']);
//          解密end
            $data[1]['value'];
            $pdo=$this->pdoTool();
            $smt=$pdo->query("select authstr from t_mpuser where wxid='{$data[1]['value']}'");
            $data= $smt->fetchAll();
            $pdo=null;
            $url = $_SERVER["HTTP_HOST"].'/?authstr.'.$data[0][0];
            $header[] = "Content-type: text/xml";//定义content-type为xml
            //回归原始接口
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $GLOBALS ["HTTP_RAW_POST_DATA"]);
            $rs=curl_exec($ch);
            curl_close($ch);
            //end
            echo $this->enCodeWX($rs);
            die();
        }
    }

    /**
     * 创建 导航
     * @param $asstok
     * @param $menu
     * @return mixed|null
     */
    function createMenu($asstok=null,$menu=null)
    {
        if (empty($asstok)) return false;
        if (empty($menu)) return false;
        $url = SQ_CREATE_MENU_POST_URL . $asstok;
        $menu = array2Json(array(
            'button' => $menu
        ));
        return $this->curls($url, $menu);
    }
    /**
     * 微信每10分钟推送ComponentVerifyTicket  获取方法 by yzy
     * 最后 ECHO SUCCESS 为官方要求 请勿去除
     */
    function getComponentVerifyTicket(){
        if(!empty( $GLOBALS ["HTTP_RAW_POST_DATA"])){
        if (class_exists("Redis")) {
            $redis = new \Redis();
            $redis->connect(REDISCONFIG_HOST, REDISCONFIG_PORT);
            $redis->select(1);
            if (!empty($redis->socket)) {
                $datasget = $_GET;
                include_once "wxBizMsgCrypt.php";
                $xml = $GLOBALS ["HTTP_RAW_POST_DATA"];
                $vals = $this->xmlToArr($xml);
                $msg=$this->getComponentVerifyTicketBase($datasget,$vals[3]['value']);
                if(is_array($msg)){
                $redis->set("component_verify_ticket", $msg[7]['value']);
                }else
                {
                $redis->set("itestdatas_yzy_xml", $GLOBALS ["HTTP_RAW_POST_DATA"]);
                $redis->set("itestdatas_yzy_get", json_encode($_GET));
                $redis->set("itestdatas_yzy_erro", $msg);
                }
            }
        }
        }
        echo "success";
        die();
    }
    /**
     * 获取第三方平台component_access_token
     * @return bool|string
     */
    function getComponentAccessToken(){
        if (class_exists("Redis")) {
            $redis = new \Redis();
            $redis->connect(REDISCONFIG_HOST, REDISCONFIG_PORT);
            if (!empty($redis->socket)) {
                if ($redis->exists("component_access_token")) {
                    $temp=$redis->get("component_access_token");
                    if($temp!=''){
                         return $temp;
                    }
                    else{
                        return null;
                    }
                } else {
                    $redis->select(1);
                    $vals = $redis->get("component_verify_ticket");
                    $component_access_tokendata=$this->getComponentAccessTokenBase($vals);
                    if(empty($component_access_tokendata['component_access_token'])) return null;
                    $redis->select(0);
                    $redis->set("component_access_token",$component_access_tokendata['component_access_token']);
                    $redis->expire("component_access_token", 6800);
                    return $component_access_tokendata['component_access_token'];
                }
            }
        }
    }

    /**
     * 获取预授权码pre_auth_code
     * @return bool|string
     */
    function getPreAuthCode(){
        if (class_exists("Redis")){
            $redis = new \Redis();
            $redis->connect(REDISCONFIG_HOST, REDISCONFIG_PORT);
            if (!empty($redis->socket)) {
                    $preauthcode=$this->getPreAuthCodeBase();
                    if(empty($preauthcode)||empty($preauthcode['pre_auth_code'])) return null;
                    return $preauthcode['pre_auth_code'];
            }
        }
        die();
    }

    /**
     * 创建关注公众号连接
     * @param null $urls
     */
    function createLink($urls=null){
       $pre= $this->getPreAuthCode();
       empty($urls)&&($url="http://xin.zhidianyun.com/?uid=1") ||$url=$urls;
       if(!empty($urls))
       return  "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=".COMPONENT_APPID."&pre_auth_code={$pre}&redirect_uri={$url}";
    }
    /**
     * 微信全网发布吊用验证方法
     */
    function wxRelease(){
        $release_appid="wx570bc396a51b8ff8";
        $release_username="gh_3c884a361561";
        if (! isset ( $GLOBALS ["HTTP_RAW_POST_DATA"] )) {
            return false;
        }
        $xml = $GLOBALS ["HTTP_RAW_POST_DATA"];
        //debug
        $redis=new \Redis();
        $redis->connect(REDISCONFIG_HOST,REDISCONFIG_PORT);
        $redis->select(1);
        if(1){
            $datasget = $_GET;
            $vals = $this->xmlToArr($xml);
            //debugs
        $redis->set("oldxml", $xml);
        $redis->set("xmldata", json_encode($vals));
        $redis->set("getdata", json_encode($datasget));
            //end
//AppSecret
            if(!defined("COMPONENT_APPSECRET"))
                define('COMPONENT_APPSECRET', "7b91bd69f3f1fcd32bdf5f3d55be7123");
//TOKEN
            if(!defined("SQ_TOKEN"))
                define('SQ_TOKEN', "ZDYTOKENS");
//公众号消息加解密Key
            if(!defined("SQ_ENCODINGAESKEY"))
                define('SQ_ENCODINGAESKEY', "qwertyuioplkjhgfdsamnbvcxzqwertyuioplkjhgfd");

            $token = SQ_TOKEN;
            $appId = COMPONENT_APPID;
            $encodingAesKey = SQ_ENCODINGAESKEY;
            include_once __DIR__."/wxBizMsgCrypt.php";
            $pc = new WXBizMsgCrypt($token, $encodingAesKey, $appId);

            $msg = '';
            $timeStamp = $datasget['timestamp'];
            $nonce = $datasget['nonce'];
            $msg_sign = $datasget['msg_signature'];
            //debug
        $redis->set("decryptMsg", json_encode(array($timeStamp,$nonce,$msg_sign)));

            $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
            $from_xml = sprintf($format, $vals[3]['value']);
            $err = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
            //debug
        $redis->set("erro", $err);
            $xml=$msg;
            $message = simplexml_load_string ( $xml, 'SimpleXMLElement', LIBXML_NOCDATA );

            //debug
        $redis->set("newxml", $xml);
        $redis->set("msgxmldecode", json_encode($message));


            //模拟测试1 yes
            if(!empty($message->Event)||$message->AuthorizerAppid[0].""==$release_appid){
                //debug
        $redis->set("debug1",  json_encode($message));
                $con=$message->Event[0].""."from_callback";
                $times=time();
                $ToUserName=    $message->FromUserName[0]."";
                $FromUserName=    $message->ToUserName[0]."";
                $reXml="<xml>
<ToUserName><![CDATA[{$ToUserName}]]></ToUserName>
<FromUserName><![CDATA[{$FromUserName}]]></FromUserName>
<CreateTime>{$times}</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[{$con}]]></Content>
</xml>";
                $encryptMsg = '';
                $pc->encryptMsg($reXml, $timeStamp, $nonce, $encryptMsg);
                echo $encryptMsg;
                die();
            }
            //模拟测试2 yes
            if(!empty($message->Content)&&$message->Content[0].""=="TESTCOMPONENT_MSG_TYPE_TEXT"&&$message->ToUserName[0].""==$release_username){
                //debug
        $redis->set("debug2", json_encode($message));
                $con="TESTCOMPONENT_MSG_TYPE_TEXT_callback";
                $times=time();
                $ToUserName=    $message->FromUserName[0]."";
                $FromUserName=    $message->ToUserName[0]."";
                $reXml="<xml>
<ToUserName><![CDATA[{$ToUserName}]]></ToUserName>
<FromUserName><![CDATA[{$FromUserName}]]></FromUserName>
<CreateTime>{$times}</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[{$con}]]></Content>
</xml>";
                $encryptMsg = '';
                $pc->encryptMsg($reXml, $timeStamp, $nonce, $encryptMsg);
                echo $encryptMsg;
                die();
            }


            //模拟测试3 no
            if(!empty($message->Content)&&strstr($message->Content[0]."","QUERY_AUTH_CODE")&&$message->ToUserName[0].""==$release_username){
                //debug
            $redis->set("debug3",json_encode($message));
                $con=$message->Content[0]."";
                $code= explode(":",$con);
                if(empty($code[1])) return 0;
                $code=$code[1];
                $info=$this->getUserInfo($code);
                //debug
            $redis->set("debug4",json_encode($info));
                $url="https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$info['authorization_info']['authorizer_access_token'];
                //debug
            $redis->set("debug5",$url);
                $json='{
"touser":"'.$message->FromUserName[0]."".'",
"msgtype":"text",
"text":
{
     "content":"'.$code.'_from_api"
}
}';
                $res=$this->curls($url,$json);
                //debug
                $redis->set("debug6",$res);
                die();
            }
        }
    }

}