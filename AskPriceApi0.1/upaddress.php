<?php
define('ACC',true);
require('./include/init.php');
$data = $_POST;

$addadress = new AddressModel();
$addadress->is_login();
$id = isset($_POST['address_id'])?$_POST['address_id']+0:0;
//自动验证

if(!$addadress->_validate($data)){
    $msg = implode('/r/n',$addadress->getErr());
    $errcode = implode('/r/n',$addadress->getErrCode());
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
    $response = ch_json_encode($response);
    exit($response);
}
//自动过滤
$data=$addadress->_facade($data);

//自动填充
$data=$addadress->_autofill($data);
$data['temp_buyers_id'] = $_SESSION['temp_buyers_id'];

 
if($addadress->checksame($data,$id)){
    $data['temp_buyers_address_id'] = $id;
    $response = array('success'=>'true','data'=>$data);
    $response = ch_json_encode($response);
    exit($response);
 }
if($data['defaultaddress']==1){
        $arr = array('defaultaddress'=>0);
        $row = $addadress->update($arr,$_SESSION['temp_buyers_id']);
}
if($addadress->updateaddress($data,$_SESSION['temp_buyers_id'],$id)){
    $data['temp_buyers_address_id'] = $id;
    $response = array('success'=>'true','data'=>$data);
    $response = ch_json_encode($response);
    exit($response);
}else{
    $response = array("success"=>"false","error"=>array("msg"=>'修改地址失败','code'=>4111));
    $response = ch_json_encode($response);
    exit($response);
}
?>