<?php
define('ACC',true);
require('./include/init.php');
$favorite = new FavoriteModel();
$favorite->is_login();
$list = $favorite->myfavoritelist($_SESSION['temp_buyers_id']);
if(empty($list)){
	   $response = json_encode(array('success'=>'true','data'=>array()));
    exit($response);

}

foreach($list as $k=>$v){
$list[$k]['photo'] = NROOT.'/Guest/'.$v['photo'];
}

$response = array('success'=>'true','data'=>$list);
$response = ch_json_encode($response);
exit($response);
















?>