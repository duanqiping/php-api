<?php
//单文件上传
define('ACC',true);
require('./include/init.php');
$uptool = new UpsigleTool();
$ori_img = $uptool->up('ori_img');

if($ori_img) {
    $data['img_url'] = $ori_img;

}else{
	$erro = $uptool->getErr();
	$response = array("success"=>"false","error"=>array("msg"=>$erro,'code'=>4101));
    $response = ch_json_encode($response);
    exit($response);
}


// 如果$ori_img上传成功,再次生成中等大小缩略图 300*400
// 根据原始地址 定 中等图的地址
// 例:aa.jpeg --> goods_aa.jpeg

if($ori_img) {

   	$ori_img = MROOT .'Guest/'. $ori_img; // 加上绝对路径 

    $goods_img = dirname($ori_img) . '/thumb_' . basename($ori_img);
    if(ImageTool::thumb($ori_img,$goods_img,200,200)) {
        $data['goods_img'] = str_replace(MROOT .'Guest/','',$goods_img);

    }



}

$arr = array();
$arr['img_url'] = NROOT .'/Guest/'.$data['img_url'];
$arr['thumb_url'] = NROOT .'/Guest/'.$data['goods_img'];

    $response = array('success'=>'true','data'=>$arr);
    $response = ch_json_encode($response);
    exit($response);



















?>