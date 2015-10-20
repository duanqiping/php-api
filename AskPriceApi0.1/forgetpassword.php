<?php
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
//查看修改的密码与原来密码是否一致,如果返回真说明密码一致，否则说明密码不一致
$row = $user->checkUser($_SESSION['temp_buyers_mobile'],md5($data['temp_buyers_password']));
if($row){
        $response = array("success"=>"true","data"=>array("msg"=>'密码修改成功'));
        $response = ch_json_encode($response);
        exit($response);
}
//修改密码
$data = array('temp_buyers_password'=>md5($data['temp_buyers_password']));
if($user->updateinfo($data,$_SESSION['temp_buyers_mobile'])){
        $e = new EasemobModel();
        $options = array();
        $options['username'] = $_SESSION['temp_buyers_mobile'];//用户名            
        $options['password'] = $row['temp_buyers_password']; //密码           
        $options['newpassword'] = md5($data['temp_buyers_password']); //新密码     
        $e->editPassword($options);
        $response = array("success"=>"true","data"=>array("msg"=>'密码修改成功'));
        $response = ch_json_encode($response);
        exit($response);

}else{
    $response = array("success"=>"false","error"=>array("msg"=>'密码修改失败','code'=>4117));
    $response = ch_json_encode($response);
    exit($response);

}



?>