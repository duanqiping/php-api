<?php
define('ACC',true);
require('./include/init.php');
$addadress = new AddressModel();
$addadress->is_login();
$uid = $_SESSION['temp_buyers_id'];

$data = $addadress->defaultaddress($uid);
if(empty($data)){
$jobj=new stdclass();
$response = json_encode(array('success'=>'true','data'=>$jobj));
exit($response);
}
    $response = array('success'=>'true','data'=>$data);
    $response = ch_json_encode($response);
    exit($response);
?>