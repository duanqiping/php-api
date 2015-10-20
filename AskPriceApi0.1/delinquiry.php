<?php
//删除询价单
define('ACC',true);
require('./include/init.php');
$request_id = isset($_POST['request_id'])?$_POST['request_id']+0:0;
if($request_id){
	$Subinquiry = new SubinquiryModel();
$Subinquiry->is_login();
	$data = array('state'=>2);
	if($Subinquiry->update($data,$request_id)){
    $response = array("success"=>"true","data"=>array("msg"=>'删除询价单成功'));
    $response = ch_json_encode($response);
    exit($response);
	}else{
		$response = array("success"=>"false","error"=>array("msg"=>'删除询价单失败','code'=>4114));
	    $response = ch_json_encode($response);
	    exit($response);

	}

}
    $response = array("success"=>"false","error"=>array("msg"=>'request_id不能为空','code'=>4800));
    $response = ch_json_encode($response);
    exit($response);
?>