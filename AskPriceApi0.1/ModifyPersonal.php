<?php
//修改个人资料，昵称，介绍，上传头像
define('ACC',true);
require('./include/init.php');

$data = $_POST;

//自动验证

			$user = new UserModel();
			/*if(!$user-> _validate($data)){
				   $msg = implode('/r/n',$user->getErr());
			    $errcode = implode('/r/n',$user->getErrCode());
			    $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
			    $response = ch_json_encode($response);
			    exit($response);
			}*/
			//修改
$user->is_login();
			//  上传头像
									$uptool = new UpHeadTool();
									$ori_img = $uptool->up('ori_img',$_SESSION['temp_buyers_mobile']);


									if( !$ori_img ) {

											$error = $uptool->getErr();
											if($error != 0){
													$response = array("success"=>"false","error"=>array("msg"=>$error,'code'=>4101));
									    $response = ch_json_encode($response);
									    exit($response);
											}

									}


									// 如果$ori_img上传成功,生成缩略图 
									// 根据原始地址 定 中等图的地址

									if($ori_img) {

									   // $ori_img = MROOT . $ori_img; // 加上绝对路径 

									   /* // 生成浏览时用缩略图 160*220
									    // 定好缩略图的地址
									    // aa.jpeg --> thumb_aa.jpeg
									    $thumb_img = dirname($ori_img) . '/thumb_' . basename($ori_img);

									    if(ImageTool::thumb($ori_img,$thumb_img,160,220)) {
									        $data['photo'] = str_replace(ROOT.'Guest/','',$thumb_img);
									    }*/
									   
 										$data['photo'] = str_replace('Guest/','',$ori_img);
 									
									}

   if($user->checksame($data,$_SESSION['temp_buyers_mobile'])){
   	$info = $user->getuserinfo($_SESSION['temp_buyers_mobile']);
				$info['photo'] = NROOT.'/Guest/'.$info['photo'];
    $response = array('success'=>'true','data'=>$info);
    $response = ch_json_encode($response);
    exit($response);
   }
   //自动过滤
   $data = $user->_facade($data);
			if($user->updateinfo($data,$_SESSION['temp_buyers_mobile'])){
				$info = $user->getuserinfo($_SESSION['temp_buyers_mobile']);
				$info['photo'] = NROOT.'/Guest/'.$info['photo'];

    $response = array('success'=>'true','data'=>$info);
    $response = ch_json_encode($response);
    exit($response);

			}else{
							$msg = '修改个人资料失败';
				   $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4100));
			    $response = ch_json_encode($response);
			    exit($response);

			}

?>