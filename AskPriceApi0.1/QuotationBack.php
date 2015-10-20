<?php
define('ACC',true);
require('./include/init.php');
$quotation_id = isset($_POST['quotation_id'])?$_POST['quotation_id']+0:0;
if($quotation_id == 0 ){
		 $response = array("success"=>"false","error"=>array("msg"=>'quotation_id不存在','code'=>4800));
  $response = ch_json_encode($response);
  exit($response);

}
$quotation = new QuotationModel();
$quotation->is_login();
$data = array('state'=>1);
$quotation->update($data,$quotation_id);
$response = array("success"=>"true","data"=>array("msg"=>'报价单已回退给卖家'));
$response = ch_json_encode($response);
echo($response);
?>