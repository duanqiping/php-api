<?php
//获取个人资料
define('ACC',true);
require('./include/init.php');

$user = new UserModel();
$user->is_login();
if($row = $user->checkUser($_SESSION['temp_buyers_mobile'],$_SESSION['temp_buyers_password'])){
    if($row['photo']){
    $row['photo'] = NROOT.'/Guest/'.$row['photo'];

    }else{
    $row['photo'] = '';  
    }
    if($row['info']){
    $row['info'] = $row['info'];
    }else{
    $row['info'] = '';  
    }
    unset($row['temp_buyers_password']);
    $response = array('success'=>'true','data'=>$row);
    $response = ch_json_encode($response);
    exit($response);
}else{
    $msg = '获取个人资料失败';
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4105));
    $response = ch_json_encode($response);
    exit($response);

}


    
?>