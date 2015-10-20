<?php
//注册
define('ACC',true);
require('./include/init.php');
$data = $_POST;
$user = new UserModel();
//echo $_SESSION['temp_buyers_mobile'];
//自动验证
if(!$user->_validate($data)){
    $msg = implode('/r/n',$user->getErr());
    $errcode = implode('/r/n',$user->getErrCode());
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
    $response = ch_json_encode($response);
    exit($response);
}


$data['nick'] = isset($_POST['nick'])?trim($_POST['nick']):'品材用户';
$data['temp_buyers_mobile'] = $_SESSION['temp_buyers_mobile'];
//自动过滤
$data=$user->_facade($data);
//自动填充
$data=$user->_autofill($data);

if($user->reg($data)){
    //把验证短息清空
    $checks = new CheckCodeModel();
    $checks->delcodesix($data['temp_buyers_mobile']);
    $data = $user->checkUser($data['temp_buyers_mobile'],md5($data['temp_buyers_password']));
    $_SESSION = $data;
    setcookie('remuser',$data['temp_buyers_mobile'],time()+14*24*3600);
    $response = array('success'=>'true','data'=>array('temp_buyers_id'=>$data['temp_buyers_id'],'temp_buyers_mobile'=>$data['temp_buyers_mobile'],'nick'=>$data['nick']));


        //记录到登录表
        //判断登陆表是否有记录
        $useronline = new UserOnLineModel();

        //自动填充
        $useronlinedata = array('online_buyers_id'=>$data['temp_buyers_id']);
        $useronlinedata=$useronline->_autofill($useronlinedata);
        $useronline->addlogin($useronlinedata);
       //注册用户到环信

        $e = new EasemobModel();
 
         //把用户注册到环信
         $useinfo=array();
         $useinfo['username'] = $data['temp_buyers_mobile'];
         $useinfo['password'] = $data['temp_buyers_password'];
         $e->openRegister($useinfo);
            $response = ch_json_encode($response);
         exit($response);

}else{
    $response = array("success"=>"false","error"=>array('msg'=>'用户注册失败','code'=>4113));
    $response = ch_json_encode($response);
    exit($response);

}



?>