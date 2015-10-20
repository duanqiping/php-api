<?php
define('ACC',true);
require('./include/init.php');
$addadress = new AddressModel();
$addadress->is_login();
$uid = $_SESSION['temp_buyers_id'];

$data = $addadress->select($uid);
if(empty($data)){
    $response = json_encode(array('success'=>'true','data'=>array()));
    exit($response);
}
    $response = array('success'=>'true','data'=>$data);
    $response = ch_json_encode($response);
    exit($response);
?>