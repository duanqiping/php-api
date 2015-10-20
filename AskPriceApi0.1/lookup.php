<?php
define('ACC',true);
require('./include/init.php');
$mobile = isset($_POST['mobile'])?$_POST['mobile']+0:0;
$page = isset($_POST['page'])?$_POST['page']+0:1;
if($page < 1) {
    $page = 1;
}
//每页显示多少条
$limit = isset($_POST['limit'])?$_POST['limit']+0:5;
$user = new UserModel;
$user->is_login();
$items = $user->lookupinfo($mobile,$page,$limit);
if(empty($items)){
    $response = json_encode(array('success'=>'true','data'=>array()));
    exit($response);
}
//返回数据给APP

	    foreach($items as $k=>$row){
				    	if($row['photo']){
				     $items[$k]['photo'] = NROOT.'/Guest/'.$row['photo'];

				    }else{
				    $items[$k]['photo'] = '';  
				    }
				    if($row['info']){
				    $items[$k]['info'] = $row['info'];
				    }else{
				    $items[$k]['info'] = '';  
				    }
    	  //判断是否收藏过
				    $favorit = new FavoriteModel;
				    $fs = $favorit->is_friend($_SESSION['temp_buyers_id'],$row['temp_buyers_id']);
				    if($fs>0){//收藏过
      					$items[$k]['is_collection'] = 1;
				    }else{//没有收藏
											$items[$k]['is_collection'] = 0;
				    }

    }

	    $response = array('success'=>'true','data'=>$items);
     $response = ch_json_encode($response);
     exit($response);

?>