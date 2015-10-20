<?php
//获取个人资料
define('ACC',true);
require('./include/init.php');
$mobile = isset($_POST['temp_buyers_mobile'])?$_POST['temp_buyers_mobile']:0;
if(!$mobile){
    $msg = '手机号码不能为空';
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4800));
    $response = ch_json_encode($response);
    exit($response);
}

$user = new UserModel();
$user->is_login();
/*if(!$user->_validate($_POST)) { // 如果数据检验没通过,报错退出.
        $msg = implode('/r/n',$user->getErr());
        $errcode = implode('/r/n',$user->getErrCode());
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
        $response = ch_json_encode($response);
        exit($response);
    }*/
if($row = $user->getuserinfo($mobile)){
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