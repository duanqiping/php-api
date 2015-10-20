<?php
/***
用户登陆页面
***/

define('ACC',true);
require('./include/init.php');

$_POST['act'] = "login";
$_POST['temp_buyers_mobile'] = "18621715257";
$_POST['temp_buyers_password'] = "123456";

if(isset($_POST['act'])) {
    // 这说明是点击了登陆按钮过来的
    // 收用户名/密码,验证....
    if(!isset($_POST['temp_buyers_mobile'])){
    $response = array("success"=>"false","error"=>array("msg"=>'你还没有登录','code'=>4120));
    $response = ch_json_encode($response);
    exit($response);
    }
    $u = $_POST['temp_buyers_mobile'];
    $p = $_POST['temp_buyers_password'];

    // 合法性检测
    $user = new UserModel();
    //验证
    if(!$user->check($u,'require')){
    $response = array("success"=>"false","error"=>array("msg"=>'手机号必须存在','code'=>4800));
    $response = ch_json_encode($response);
    exit($response);
    }
    if(!$user->check($u,'mobile')){
    $response = array("success"=>"false","error"=>array("msg"=>'手机号格式不正确','code'=>4800));
    $response = ch_json_encode($response);
    exit($response);
    }
    if(!$user->check($p,'require')){
    $response = array("success"=>"false","error"=>array("msg"=>'密码不能为空','code'=>4800));
    $response = ch_json_encode($response);
    exit($response);
    }

    if(!$user->checkUser($u)) {
            $msg = '用户不存在';
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4116));
            $response = ch_json_encode($response);
            exit($response);
        }
    // 核对用户名,密码
    $row = $user->checkUser($u,md5($p));
    if(empty($row)) {
    $response = array("success"=>"false","error"=>array("msg"=>'用户名密码不匹配!','code'=>4118));
    $response = ch_json_encode($response);
    exit($response);
    } else {
        $_SESSION = $row;
        setcookie('remuser',$u,time()+14*24*3600);
        //记录到登录表
        //判断登陆表是否有记录
        $useronline = new UserOnLineModel();
        if($useronline->count($row['temp_buyers_id'])){ //修改下登录时间    
            $useronline->updatetime(array('active_time'=>time()),$row['temp_buyers_id']);
        }else{
            //自动填充
            $useronlinedata = array('online_buyers_id'=>$row['temp_buyers_id']);
            $useronlinedata=$useronline->_autofill($useronlinedata);
            $useronline->addlogin($useronlinedata);
        }
        $response = array('success'=>'true','data'=>array('temp_buyers_id'=>$row['temp_buyers_id'],'temp_buyers_mobile'=>$row['temp_buyers_mobile'],'nick'=>$row['nick'],'photo'=>NROOT.'/Guest/'.$row['photo'],'info'=>$row['info']));
        $response = ch_json_encode($response);
        exit($response);

    }

} else {
    echo "让他登录去";
    $remuser = isset($_COOKIE['remuser'])?$_COOKIE['remuser']:'';
    //让他登录去
}

