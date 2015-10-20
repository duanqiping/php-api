<?php
define('ACC',true);
require('./include/init.php');
$favorite = new  FavoriteModel();
$favorite->is_login();
//接收对方的
if(!$favorite->_validate($_POST)){
    $msg = implode('/r/n',$favorite->getErr());
    $errcode = implode('/r/n',$favorite->getErrCode());
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
    $response = ch_json_encode($response);
    exit($response);
}
$_POST['to_id'] = $_POST['to_id']+0;
if($_POST['to_id'] == $_SESSION['temp_buyers_id']){
		  $response = array("success"=>"false","error"=>array('msg'=>'自己不能收藏自己','code'=>4800));
    $response = ch_json_encode($response);
    exit($response);

}
$data = $favorite-> _facade($_POST);
$data = $favorite-> _autoFill($data);
$data['from_id'] = $_SESSION['temp_buyers_id'];
//判断是否有这个用户
$user = new UserModel;
$toinfo = $user->find($data['to_id']);
if(!$toinfo){
	   $response = array("success"=>"false","error"=>array('msg'=>'无此用户','code'=>4800));
    $response = ch_json_encode($response);
    exit($response);
}
//判断是否收藏过
$isf = $favorite->is_friend($_SESSION['temp_buyers_id'],$data['to_id']);
if($isf){
    $response = array("success"=>"false","error"=>array('msg'=>'已经收藏过此好友','code'=>4140));
    $response = ch_json_encode($response);
    exit($response);
}
//写入数据
if($favorite->add($data)){
    if($favorite->insert_id()>0){
    $response = array("success"=>"true","data"=>array("msg"=>'收藏好友成功'));
    $response = ch_json_encode($response);
    exit($response);
    }

}

    $response = array("success"=>"false","error"=>array('msg'=>'收藏好友失败','code'=>4139));
    $response = ch_json_encode($response);
    exit($response);



























?>

