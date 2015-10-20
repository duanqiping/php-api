<?php
//取出我的一条问价对应的报价单列表是哪个报价商，总报价
define('ACC',true);
require('./include/init.php');

$page = isset($_POST['page'])?$_POST['page']+0:1;
if($page < 1) {
    $page = 1;
}
//每页显示多少条
$limit = isset($_POST['limit'])?$_POST['limit']+0:5;
$request_id = isset($_POST['request_id'])?$_POST['request_id']+0:0;
if($request_id==0){
	    $msg = 'request_id必须存在';
    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4800));
    $response = ch_json_encode($response);
    exit($response);

}
$quotation = new QuotationModel();
$quotation->is_login();
$items = $quotation->MyrequestToQuotationlist($request_id,$page,$limit);
if(empty($items)){
    $response = json_encode(array('success'=>'true','data'=>array()));
    exit($response);
}
$response = array('success'=>'true','data'=>$items);
$response = ch_json_encode($response);
exit($response);

?>