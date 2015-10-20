<?php
define('ACC',true);
require('./include/init.php');

$user = new UserModel();
$checks = new CheckCodeModel();
$_POST['mobile'] = @$_POST['temp_buyers_mobile'];
//$_POST['mobile'] = "18621715257";//我
//$_POST['checkcode'] = "1234";
//$_POST['type'] = 1;
////session_start();
//$_SESSION['temp_buyers_mobile'] = "1111";

if(!$checks->_validate($_POST)) {  // 自动检验

    $msg = implode('/r/n',$checks->getErr());
    $errcode = implode('/r/n',$checks->getErrCode());
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
    $response = ch_json_encode($response);

    exit($response);
}

$_POST['temp_buyers_mobile'] = $_POST['mobile'];

    if(1 == $_POST['type']+0){//要获取注册验证码
        if($user->checkUser($_POST['temp_buyers_mobile'])) {
            $msg = '用户已注册';
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4102));
            $response = ch_json_encode($response);
            exit($response);
        }
    }else if(2 == $_POST['type']+0){//要获取忘记密码验证码
        if(!$user->checkUser($_POST['temp_buyers_mobile'])) {
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
//echo $_SESSION['temp_buyers_mobile'];
$data = $_POST;
if(isset($_SESSION['temp_buyers_mobile'])){


    if($data['temp_buyers_mobile']!==$_SESSION['temp_buyers_mobile']){
        $response = array("success"=>"false","error"=>array("msg"=>'手机号未获取验证码11','code'=>4110));
        $response = ch_json_encode($response);
        exit($response);

    }
    if($data['checkcode']!==$_SESSION['checkcode']){
        $response = array("success"=>"false","error"=>array("msg"=>'验证码错误','code'=>4109));
        $response = ch_json_encode($response);
        exit($response);

    }
        $response = array("success"=>"true","data"=>array("msg"=>'验证码校验通过'));
        $response = ch_json_encode($response);
        exit($response);
}else{
        $response = array("success"=>"false","error"=>array("msg"=>'手机号未获取验证码22','code'=>4110));
        $response = ch_json_encode($response);
        exit($response);
}
?>