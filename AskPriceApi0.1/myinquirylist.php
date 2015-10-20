<?php
define('ACC',true);
require('./include/init.php');

$page = isset($_POST['page'])?$_POST['page']+0:1;
if($page < 1) {
    $page = 1;
}
//每页显示多少条
$limit = isset($_POST['limit'])?$_POST['limit']+0:5;
$inquirylist = new SubinquiryModel();
$inquirylist->is_login();
$items = $inquirylist->requestlist($page,$limit,$_SESSION['temp_buyers_id']);
if(empty($items)){
    $response = json_encode(array('success'=>'true','data'=>array()));
    exit($response);
}
//商品信息
$items = $inquirylist->getRequestGoods($items);
//附件信息
$items = $inquirylist->getRequestGoodsattch($items);
//加上报价人数量
$data = $inquirylist->getForRequest($items);
$response = array('success'=>'true','data'=>$data);
$response = ch_json_encode($response);
exit($response);

?>