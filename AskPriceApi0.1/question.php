<?php
define('ACC',true);
require('./include/init.php');

//问题反馈
$data = $_POST;
$ques = new QuestionModel();
$ques->is_login();
if(!$ques->_validate($data)){
    $msg = implode('/r/n',$ques->getErr());
    $errcode = implode('/r/n',$ques->getErrCode());
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
    $response = ch_json_encode($response);
    exit($response);
}
if(!isset($_SESSION['temp_buyers_mobile'])){
    $msg = 'session过期';
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4120));
    $response = ch_json_encode($response);
    exit($response);
}
$data['mobile'] = $_SESSION['temp_buyers_mobile'];
//自动过滤
$data=$ques->_facade($data);
//自动填充
$data=$ques->_autofill($data);
if($ques->rep($data)){
    $response = array("success"=>"true","data"=>array("msg"=>'反馈问题成功'));
    $response = ch_json_encode($response);
    exit($response);

}else{
    $response = array("success"=>"false","error"=>array('msg'=>'反馈问题失败','code'=>4115));
    $response = ch_json_encode($response);
    exit($response);

}

?>