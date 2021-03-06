<?php
define('ACC',true);
require('./include/init.php');
//接收request_id=询价单ID
if(isset($_POST['quotation_id'])){
 	$id = $_POST['quotation_id']+0;
 	$type = 'quotation';
}else if(isset($_POST['request_id'])){
		$id = $_POST['request_id']+0;
		$type = 'request';

}else{
	 $response = array("success"=>"false","error"=>array("msg"=>'quotation_id或request_id不存在','code'=>4800));
  $response = ch_json_encode($response);
  exit($response);
}

$quotationinfo = new QuotationModel();
$quotationinfo->is_login();
//判断来查看的是报价单的报价人还是问价人
     if(isset($_POST['quotation_id'])){
         $shenfen = $quotationinfo->find($_POST['quotation_id']+0);
     }else if(isset($_POST['request_id'])){
         $shenfen = $quotationinfo->userid($_POST['request_id']+0,$_SESSION['temp_buyers_id']);
     }
     if($shenfen['buyers_id']== $_SESSION['temp_buyers_id']){//买家
       //判断是否收藏了卖家
        $favorite = new FavoriteModel;
          if($favorite->is_friend($_SESSION['temp_buyers_id'],$shenfen['suppliers_id'])){
            $is_collection = 1;    
          }else{
            $is_collection = 0; 
          }
        //把报价单is_read状态改为1
        $is_read = array('is_read'=>1);
        $quotationinfo->update($is_read,$shenfen['quotation_id'];

     }else if($shenfen['suppliers_id']== $_SESSION['temp_buyers_id']){//卖家


     }else{
        $response = array("success"=>"false","error"=>array("msg"=>'你无权查看该订单详情','code'=>4800));
        $response = ch_json_encode($response);
        exit($response);
     }



//调用SubinquiryModel的一个方法取出
$items = $quotationinfo->quotationinfo($id,$_SESSION['temp_buyers_id'],$type);
if(empty($items)){
$jobj=new stdclass();
$response = json_encode(array('success'=>'true','data'=>$jobj));
exit($response);
}

//商品信息
$items = $quotationinfo->getQuotationGoods($items);
//附件信息
$items = $quotationinfo->getRequestGoodsattch($items);
$items = $items[0];
//判断此报价单是否已经下过单
$purchase = new PurchaseModel;
$isnoe = $purchase->is_doneorder($id);
if($isnoe){//下过
  $items['is_order'] = 1;
}else{//没有下过
   $items['is_order'] =0;
}
//返回数据给APP
if(isset($is_collection)){
    $items['is_collection'] = $is_collection;
}
//返回数据给APP


	   $response = array('success'=>'true','data'=>$items);
     $response = ch_json_encode($response);
     exit($response);



?>