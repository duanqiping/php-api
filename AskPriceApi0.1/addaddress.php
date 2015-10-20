<?php
define('ACC',true);
require('./include/init.php');
$data = $_POST;

$addadress = new AddressModel();
$addadress->is_login();
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

$count = $addadress->count($data['temp_buyers_id']);
/*if($count >10){
    $response = array("success"=>"false","error"=>array("msg"=>'添加地址最多只能添加10个','code'=>4800));
    $response = ch_json_encode($response);
    exit($response);
}else */if($count == 0){//说明第一次填地址，默认为defaultaddress为1
    $data['defaultaddress'] = 1;

}else if($count>0){//如果新增的地址defaultaddress为1，则把其他的defaultaddress为0
    if($data['defaultaddress']==1){
        $arr = array('defaultaddress'=>0);
        $row = $addadress->update($arr,$_SESSION['temp_buyers_id']);
        if($row===false){
            $response = array("success"=>"false","error"=>array("msg"=>'更改默认地址失败','code'=>4900));
            $response = ch_json_encode($response);
            exit($response);
        }
    }

}
 if($addadress->add($data)) {
    $data['temp_buyers_address_id'] = $addadress->insert_id();
    $response = array("success"=>"true","data"=>$data);
    $response = ch_json_encode($response);
    exit($response);
 }else{
 	$response = array("success"=>"false","error"=>array("msg"=>'添加地址失败','code'=>4107));
    $response = ch_json_encode($response);
    exit($response);
 }


?>