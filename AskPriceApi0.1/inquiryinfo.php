<?php
define('ACC',true);
require('./include/init.php');
//接收request_id=询价单ID
if(isset($_POST['request_id'])){
 	$request_id = $_POST['request_id']+0;
}else{
	 $response = array("success"=>"false","error"=>array("msg"=>'request_id不存在','code'=>4800));
  $response = ch_json_encode($response);
  exit($response);
}
$inquiryinfo = new SubinquiryModel();
$inquiryinfo->is_login();
//调用SubinquiryModel的一个方法取出
$row = $inquiryinfo->find($request_id);
//判断是私密还是公开，如果是私密只能是对方可看。
if($row['type'] !=0 ){ //私密，你的登录ID必须为报价ID
  if($row['suppliers_id'] != $_SESSION['temp_buyers_id']){
		 $response = array("success"=>"false","error"=>array("msg"=>'你无权查看该订单详情','code'=>4800));
	  $response = ch_json_encode($response);
	  exit($response);
  }
}
$items = $inquiryinfo->inquiryinfo($request_id);
if(empty($items)){
$jobj=new stdclass();
$response = json_encode(array('success'=>'true','data'=>$jobj));
exit($response);
}

//商品信息
$items = $inquiryinfo->getRequestGoods($items);



//附件信息
$items = $inquiryinfo->getRequestGoodsattch($items);
//判断是否已经收藏问价人
//print_r($items[0]['buyers']['temp_buyers_id']);
if($row['temp_buyers_id'] != $_SESSION['temp_buyers_id']){
			$favorite = new FavoriteModel;
	  if($favorite->is_friend($_SESSION['temp_buyers_id'],$row['temp_buyers_id'])){
        $items[0]['is_collection'] = 1;    
	  }else{
								$items[0]['is_collection'] = 0; 
	  }
}


//是否报过价
$items = $inquiryinfo->myisquotationtoask($items,$_SESSION['temp_buyers_id']);


//返回数据给APP


	    $response = array('success'=>'true','data'=>$items[0]);
     $response = ch_json_encode($response);
     exit($response);



?>