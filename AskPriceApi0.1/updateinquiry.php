<?php
//修改询价单，还没有报价单前都可以去修改询价单
define('ACC',true);
require('./include/init.php');
$request_id = isset($_POST['request_id'])?$_POST['request_id']+0:0;
if(!$request_id){
        $msg = 'request_id不能为空';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4800));
        $response = ch_json_encode($response);
        exit($response);

}
$subinquiry = new SubinquiryModel();
$subinquiry->is_login();
//判断是否有报价，有不能做修改
if($subinquiry->is_quotation($request_id)>0){
        $msg = '此报价不能做修改';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4119));
        $response = ch_json_encode($response);
        exit($response);

}
//可以修改
    if(!$subinquiry->_validate($_POST)) { // 如果数据检验没通过,报错退出.
        $msg = implode('/r/n',$subinquiry->getErr());
        $errcode = implode('/r/n',$subinquiry->getErrCode());
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
        $response = ch_json_encode($response);
        exit($response);
    }

    // 自动过滤
    $data = $subinquiry->_facade($_POST);
    // 从session读
    $data['temp_buyers_id'] = isset($_SESSION['temp_buyers_id'])?$_SESSION['temp_buyers_id']:0;


    //is_check 1 通过 0 正在审核中,如果询价单是公开的，为0，私密的为1
    $row = $subinquiry->find($request_id);
    if($row['type'] == 0){
        $data['is_check'] = 1;//此处记得改为0
    }else{
        $data['is_check'] = 1;
    }
    $data['title'] = $_POST['goods'][0]['goods_name'];
    $data['comet'] = isset($_POST['comet'])?$_POST['comet']:'';
    unset($data['request_id']);
    unset($data['sn']);
    unset($data['temp_buyers_id']);
    unset($data['addtime']);
    unset($data['adminid']);
    unset($data['admin_edit_time']);
    unset($data['type']);
    unset($data['state']);
    unset($data['suppliers_id']);

    if($subinquiry->update($data,$request_id) === false) {
    
        $msg = '修改询价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4121));
        $response = ch_json_encode($response);
        exit($response);
    }
    //先删除原来对应的商品信息和附件信息
    if(!$subinquiry->delgoods($request_id)){
        $msg = '修改询价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4121));
        $response = ch_json_encode($response);
        exit($response);
    }
    /*
    要把询价单的商品写入数据库
    1个询价单里有N个商品,我们可以循环写入询价单对应的商品表ecs_temp_requestgoods表
    */
    // 接收询价单中所有的商品
    //验证产品信息
    if(!isset($_POST['goods'])){
        $data = array("success"=>"false","error"=>array("msg"=>'至少有一种产品','code'=>4800));
        $data = ch_json_encode($data);
        exit($data); 
        
    }
    
    $items = $_POST['goods']; // 询价单中所有的商品
    $cnt = 0;  // cnt用来记录插入ecs_temp_requestgoods成功的次数

    $RG = new RGModel(); // 获取ecs_temp_requestgoods的操作model

    foreach($items as $k=>$goodsvalue) {  // 循环订单中的商品,写入ecs_temp_requestgoods表
        if(!$RG->_validate($goodsvalue)) {  // 如果数据检验没通过,报错退出.
   
            $msg = implode('/r/n',$RG->getErr());
            $errcode = implode('/r/n',$RG->getErrCode());
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
            $response = ch_json_encode($response);
            exit($response);
        }
        $goodsvalue['request_id'] = $request_id;
        $goodsvalue['temp_buyers_id'] = $_SESSION['temp_buyers_id'];

            // 自动过滤
            $goodsvalue = $RG->_facade($goodsvalue);
       
        if($RG->add($goodsvalue)) {
            $cnt += 1;  // 插入一条ecs_temp_requestgoods成功,$cnt+1.
            // 因为,1个询价单有N条商品,必须N条商品,都插入成功,才算订单插入成功!
            // 获取刚刚产生的询价单order_id的值
            if($goodsvalue['is_attach'] == 1){
            $requestgoods_id = $RG->insert_id();
            //上传附件
                if($requestgoods_id>0){
                    $key = 'goodsattach'.($cnt-1);
           
                    $upTool = new UpTool();
                    //多条数据
                    if($res = $upTool->up($key)){

                        $insertids = array();
                        foreach ($res as $k=>$v){
                            //生成缩略图

                            if($v['type'] == 'pic'){
                                $ori_img = MROOT.'Guest/'.$v['file_url'];
                                
                                $thumb_img = dirname($ori_img) . '/thumb_' . basename($ori_img);
                               
                                if(ImageTool::thumb($ori_img,$thumb_img,100,100)) {
                                    
                                    $thumb_image = str_replace(MROOT.'Guest/','',$thumb_img);
                            
                                }
                            }else{
                                    $thumb_image = '';
                            }
                            $dataatt['typeid'] = $v['type'] == 'pic'?1:2;
                            $dataatt['request_id'] = $request_id;
                            $dataatt['temp_buyers_id'] = $_SESSION['temp_buyers_id'];
                            $dataatt['requestgoods_id'] = $requestgoods_id;
                            $dataatt['img_thumb'] = $thumb_image;
                            $dataatt['file_url'] = $v['file_url'];
                            $dataatt['icon_url'] = $v['icon_url'];
                            $RequestAttach = new RequestAttachModel();
                            if(!$RequestAttach->add($dataatt)) {
                                // 撤消此询价单
                               $subinquiry->invoke($request_id);
                                $msg = '修改询价单失败';
                                $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4121));
                                $response = ch_json_encode($response);
                                exit($response);
                                
                            }

                        }
                    }

                }

            }



        }else{//商品插入失败
             // 撤消此询价单
            $subinquiry->invoke($request_id);
            $msg = '修改询价单失败';
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4121));
            $response = ch_json_encode($response);
            exit($response);

        }
    }

    if(count($items) !== $cnt) { // 并没有全部入库成功.
        // 撤消此询价单
        $subinquiry->invoke($request_id);
        $msg = '修改询价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4121));
        $response = ch_json_encode($response);
        exit($response);
    }
//返回询价单详情给APP
$items = $subinquiry->inquiryinfo($request_id);
if(empty($items)){
$jobj=new stdclass();
$response = json_encode(array('success'=>'true','data'=>$jobj));
exit($response);
}

//商品信息
$items = $subinquiry->getRequestGoods($items);

//附件信息
$items = $subinquiry->getRequestGoodsattch($items);


//当公开问价的时候推送询价单到环信
if($row['type'] == 0){
$e = new EasemobModel();
 $username = array($_SESSION['temp_buyers_mobile']);
 $ext = array('type'=>2,'device'=>4,'body'=>ch_json_encode($items[0]));
 $e->yy_hxSend($from_user = "admin", $username, "Admin Message Look Ext", $target_type = "users", $ext); 
}


//返回数据给APP


    $response = array('success'=>'true','data'=>$items[0]);
    $response = ch_json_encode($response);
    exit($response);



?>