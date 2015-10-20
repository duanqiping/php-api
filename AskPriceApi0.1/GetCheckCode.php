<?php
/**
*注册页
*功能：验证手机号，发送验证码，验证验证码
*防止暴力刷短信息，和爆破短信息
*手机验证短信设计与代码实现 >1. 时效限制: [5-10min] >2. 使用次数限制: 6次 >3. IP次数限制: 防止恶意刷手机验证码短信 >4. 手机号限制: 防止短信轰炸 >5. 跨域请求限制: 进一步限制恶意刷短信 >6. 验证码验证: 进一步限制恶意刷短信 
**/
define('ACC',true);
require('./include/init.php');

$checks = new CheckCodeModel();
$user = new UserModel();
/*
调用自动检验功能
检验手机号
type
*/

if(!$checks->_validate($_POST)) {  // 自动检验
    $msg = implode('/r/n',$checks->getErr());
    $errcode = implode('/r/n',$checks->getErrCode());
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
    $response = ch_json_encode($response);
    exit($response);
}

// 检验手机号是否已存在，废弃表字段is_regist,

    if(1 == $_POST['type']+0){//要获取注册验证码
        if($user->checkUser($_POST['mobile'])) {
            $msg = '用户已注册';
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4102));
            $response = ch_json_encode($response);
            exit($response);
        }
    }else if(2 == $_POST['type']+0){//要获取忘记密码验证码
        if(!$user->checkUser($_POST['mobile'])) {
            $msg = '用户不存在';
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4116));
            $response = ch_json_encode($response);
            exit($response);
        }

    }else{
        $msg = 'type必须为1或2';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4800));
        $response = ch_json_encode($response);
        exit($response);
    }

//type=1用户不存在，可以注册，可以发验证码了
//type=2用户存在，可以发验证码了
//删除此用户这个时刻之前的短信
//查询60s内是否发送过，如果存在，需要等待 60-(已发送时间)s

    $now = time();
    $mobile = $_POST['mobile'];
    $checks->delcode($mobile, $now);
    $row = $checks->findOne($mobile, $now); 
    if($row) {//说明验证码已经发送，还在有效期内
            $diffSeconds = $now - $row['createAt'];
            if ($diffSeconds < 60) {
                //时间间隔太小，老弟你刷短信纳是吧，果断拒绝你
                $response = array("success"=>"false","error"=>array("msg"=>'时间间隔太小，请1分钟后再申请', 'code'=>4103));
                $response = ch_json_encode($response);
                exit($response);
            } else {//验证码过期
                $checks->setIsUsed(array('isUse'=>1,'usingAt'=>time()),$mobile,$row['code']);  //设置为已经使用过
            }
    } 
//查询手机号码接收次数，如果太多明显要轰炸别人，让我们背黑锅呀
$end = $now;
$begin = $now - 24 * 60 * 60;
$countmobile = $checks->count($mobile,'mobile',$begin,$end);

if($countmobile >= 10) {
    //老大，都给你手机号发6次了还收不到，你是要用短信轰炸别人呢还是真收不到，果断舍弃你这用户把
    $response = array("success"=>"false","error"=>array("msg"=>'短信今天已经发了10次，请明天再申请', 'code'=>4104));
    $response = ch_json_encode($response);
    exit($response);
}
/*//查询这个Ip发送了多少次了， 如果太多明显是来浪费我们财产来了，短信是要钱的呀老大
$ip = GetIP();
$countip = $checks->count($ip,'ip',$begin,$end);
        if ($countip >= 6) {
            //老大，你这个Ip都浪费了我5毛钱了，你还不甘心呀，算了，放弃你了
            $response = array("success"=>"false","error"=>array("msg"=>'老大，你这个Ip都浪费了我5毛钱了，你还不甘心呀，算了，放弃你了', 'code'=>4104));
            $response = ch_json_encode($response);
            exit($response);

    }*/
//限制跨域提交
//渲染页面时
$servername = $_SERVER['SERVER_NAME'];//当前运行脚本所在服务器主机的名字。
$sub_from = isset($_SERVER["HTTP_REFERER"])?$_SERVER["HTTP_REFERER"]:1;//链接到当前页面的前一页面的 URL 地址
$sub_len = strlen($servername);//统计服务器的名字长度。
$checkfrom = substr($sub_from,7,$sub_len);//截取提交到前一页面的url，不包含http:://的部分。

if($sub_from != 1 && $checkfrom != $servername){
    $response = array("success"=>"false","error"=>array("msg"=>'数据来源有误！请从本站提交！', 'code'=>4104));
    $response = ch_json_encode($response);
    exit($response);
}


  //ok,发送验证码
    $checkcode = random(6,1);
    if(1 == $_POST['type']+0){
    $message = URLEncode('您的注册验证码为：'.$checkcode.'。如需帮助请联系客服。');
    }else if(2 == $_POST['type']+0){
    $message = URLEncode('您的验证码为：'.$checkcode.'。如需帮助请联系客服。');
    }
   
    if(sendmessage($_POST['mobile'],$message)){
        //同时在数据库checkcode插入一条数据
        $data = $checks->_autoFill($_POST);  // 自动填充
        $data['code'] = $checkcode;
        $data = $checks->_facade($data);  // 自动过滤
        if(!$checks->addcheck($data)){

            $response = array("success"=>"false","error"=>array("msg"=>'检验验证码失败','code'=>4107));
            $response = ch_json_encode($response);
            exit($response);

        }
        //把手机号和验证码存到SESSION里
        $_SESSION['temp_buyers_mobile'] = $mobile;
        $_SESSION['checkcode'] =  $checkcode;
        $response = array("success"=>"true","data"=>array("msg"=>'验证码发送成功'));
        $response = ch_json_encode($response);
        exit($response);
    }else{
        $response = array("success"=>"false","error"=>array("msg"=>'验证码发送失败','code'=>4108));
        $response = ch_json_encode($response);
        exit($response);
    }






        

               
                  
           







?>