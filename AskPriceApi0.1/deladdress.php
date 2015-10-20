<?php
define('ACC',true);
require('./include/init.php');
$id = isset($_POST['temp_buyers_address_id'])?$_POST['temp_buyers_address_id']+0:0;
if(!$id){
      $response = array("success"=>"false","error"=>array("msg"=>'地址ID不能为空','code'=>4800));
    $response = ch_json_encode($response);
    exit($response);
}
$addadress = new AddressModel();
$addadress->is_login();
if($addadress->delete($id)){
    $response = array("success"=>"true","data"=>array("msg"=>'删除地址成功'));
    $response = ch_json_encode($response);
    exit($response);
}else{
    $response = array("success"=>"false","error"=>array("msg"=>'删除地址失败','code'=>4112));
    $response = ch_json_encode($response);
    exit($response);
}

?>