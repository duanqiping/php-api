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

$items = $inquirylist->requestlist($page,$limit);
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
//print_r($data);
if(isset($_SESSION['temp_buyers_id'])){
	$data = $inquirylist->myisquotationtoask($data,$_SESSION['temp_buyers_id']);
	//判断是否收藏了买家
	$favorite = new FavoriteModel;
	foreach($data as $k=>$v){
		   if($favorite->is_friend($_SESSION['temp_buyers_id'],$v['buyers']['temp_buyers_id'])>0){//已经收藏
		   		 $data[$k]['buyers']['is_collection'] = 1;
		   }else{
								$data[$k]['buyers']['is_collection'] = 0;
		   }



	}

}

$response = array('success'=>'true','data'=>$data);
$response = ch_json_encode($response);
exit($response);

?>