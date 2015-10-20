<?php
//修改报价单
define('ACC',true);
require('./include/init.php');
$quotation_id = isset($_POST['quotation_id'])?$_POST['quotation_id']+0:0;//接收报价单ID
if(!$quotation_id){
        $msg = 'quotation_id不能为空';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4800));
        $response = ch_json_encode($response);
        exit($response);

}
$quotation = new QuotationModel();
//去查下此报价单是否可以编辑，必须为回退状态state=1
$quotation->is_login();
$state = $quotation->lookstate($quotation_id);

if($state['qstate'] != 1){
            $msg = '此报价单不在回退状态内，不能修改';
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4124));
            $response = ch_json_encode($response);
            exit($response);

}

//有两种类型，type=1有问价单的报价单，2没有问价单的报价单，都是私密的

if($state['rstate'] != 2 ){

    $_POST['flow_price'] = $_POST['flow_price']+0;//接收物流费
    $_POST['total_price'] = $_POST['total_price']+0;//接收总价格

    if(!$quotation->_validate($_POST)) { // 如果数据检验没通过,报错退出.
            $msg = implode('/r/n',$quotation->getErr());
            $errcode = implode('/r/n',$quotation->getErrCode());
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
            $response = ch_json_encode($response);
            exit($response);
    }

    // 自动过滤
    $data = $quotation->_facade($_POST);
    unset($data['quotation_id']);
    // 自动填充
    $data = $quotation->_autoFill($data);

    //先修改数据报价单表
    if($quotation->update($data,$quotation_id) === false) {
        $msg = '修改报价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4125));
        $response = ch_json_encode($response);
        exit($response);
    }

    /*
    要把报价单的商品写入数据库
    1个报价单里有N个商品,我们可以循环写入报价单对应的商品表ecs_temp_quotegoods表
    */
    // 接收报价单中所有的商品
    //验证产品信息
    if(!isset($_POST['goods'])){
        $data = array("success"=>"false","error"=>array("msg"=>'至少有一种产品','code'=>4800));
        $data = ch_json_encode($data);
        exit($data); 
        
    }
    //清除掉原来报价单商品表的该报价单的商品数据
    if(!$quotation->delgoods($quotation_id)){
        $msg = '修改报价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4121));
        $response = ch_json_encode($response);
        exit($response);
    }


    $items = $_POST['goods']; // 接收商品信息
    $cnt = 0;  // cnt用来记录插入ecs_temp_quotegoods成功的次数

    $quotegoods = new QuotegoodsModel(); // 获取ecs_temp_quotegoods的操作model
 
    foreach($items as $k=>$goodsvalue) {  // 循环订单中的商品,写入ecs_temp_quotegoods表
        $goodsvalue['requestgoods_id'] = $goodsvalue['requestgoods_id']+0;//接收询价单商品ID
        $goodsvalue['is_attach'] = $goodsvalue['is_attach']+0;//接收商品是否有附件
        $goodsvalue['goods_price'] = $goodsvalue['goods_price']+0;//接收商品单价
               
        if(!$quotegoods->_validate($goodsvalue)) {  // 如果数据检验没通过,报错退出.
   
            $msg = implode('/r/n',$quotegoods->getErr());
            $errcode = implode('/r/n',$quotegoods->getErrCode());
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
            $response = ch_json_encode($response);
            exit($response);
        }


        $goodsvalue['quotation_id'] = $quotation_id;//接收的
        $goodsvalue['suppliers_id'] = $_SESSION['temp_buyers_id'];//从session读

            // 自动过滤
            $goodsvalue = $quotegoods->_facade($goodsvalue);

        if($quotegoods->add($goodsvalue)) {
            $cnt += 1;  // 插入一条ecs_temp_quotegoods成功,$cnt+1.
            // 因为,1个报价单有N条商品,必须N条商品,都插入成功,才算报价单插入成功!
         }
}
    if(count($items) !== $cnt) { // 并没有全部入库成功.
        // 撤消此报价单
        $quotation->invoke($quotation_id);
        $msg = '修改报价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4125));
        $response = ch_json_encode($response);
        exit($response);
    }

//调用SubinquiryModel的一个方法取出
$items = $quotation->quotationinfo($quotation_id,$_SESSION['temp_buyers_id']);
if(empty($items)){
$jobj=new stdclass();
$response = json_encode(array('success'=>'true','data'=>$jobj));
exit($response);
}

//商品信息
$items = $quotation->getQuotationGoods($items);
//附件信息
$items = $quotation->getRequestGoodsattch($items);

//返回数据给APP


    $response = array('success'=>'true','data'=>$items[0]);
    $response = ch_json_encode($response);
    exit($response);

}else if($state['rstate'] == 2){//没有问价单的报价单，思路：修改问价单，再修改报价单来处理
    $subinquiry = new SubinquiryModel();
    $request_id = $state['request_id'];
    $_POST['suppliers_id'] = $_SESSION['temp_buyers_id'];
    if(!$subinquiry->_validate($_POST)) { // 如果数据检验没通过,报错退出.
        $msg = implode('/r/n',$subinquiry->getErr());
        $errcode = implode('/r/n',$subinquiry->getErrCode());
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
        $response = ch_json_encode($response);
        exit($response);
    }

    // 自动过滤
    $data = $subinquiry->_facade($_POST);
    $data['title'] = $_POST['goods'][0]['goods_name'];
    $data['comet'] = isset($_POST['request_comet'])?$_POST['request_comet']:'';
    if($subinquiry->update($data,$request_id) === false) {
        $msg = '修改报价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4125));
        $response = ch_json_encode($response);
        exit($response);
    }

    //先删除原来对应的商品信息和附件信息
    if(!$subinquiry->delgoods($request_id)){
        $msg = '修改报价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4125));
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
    $i = 0;
    foreach($items as $k=>$goodsvalue) {  // 循环订单中的商品,写入ecs_temp_requestgoods表
        $i++;
        if(!$RG->_validate($goodsvalue)) {  // 如果数据检验没通过,报错退出.
   
            $msg = implode('/r/n',$RG->getErr());
            $errcode = implode('/r/n',$RG->getErrCode());
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
            $response = ch_json_encode($response);
            exit($response);
        }
        $goodsvalue['request_id'] = $request_id;
        $goodsvalue['temp_buyers_id'] = $state['temp_buyers_id'];//买家ID

            // 自动过滤
            $goodsvalue = $RG->_facade($goodsvalue);
       
        if($RG->add($goodsvalue)) {
            $cnt += 1;  // 插入一条ecs_temp_requestgoods成功,$cnt+1.
            // 因为,1个询价单有N条商品,必须N条商品,都插入成功,才算订单插入成功!
            $requestgoods_id = $RG->insert_id();
            $_POST['goods'][$i-1]['requestgoods_id'] = $requestgoods_id;

            // 获取刚刚产生的询价单order_id的值
            if($goodsvalue['is_attach'] == 1){

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
                            $dataatt['temp_buyers_id'] = $state['temp_buyers_id'];
                            $dataatt['requestgoods_id'] = $requestgoods_id;
                            $dataatt['img_thumb'] = $thumb_image;
                            $dataatt['file_url'] = $v['file_url'];
                            $dataatt['icon_url'] = $v['icon_url'];
                            $RequestAttach = new RequestAttachModel();
                            if(!$RequestAttach->add($dataatt)) {
                                // 撤消此询价单
                               $subinquiry->invoke($request_id);
                                $msg = '修改报价单失败';
                                $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4125));
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
            $msg = '修改报价单失败';
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4125));
            $response = ch_json_encode($response);
            exit($response);

        }
    }

    if(count($items) !== $cnt) { // 并没有全部入库成功.
        // 撤消此询价单
        $subinquiry->invoke($request_id);
        $msg = '修改报价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4125));
        $response = ch_json_encode($response);
        exit($response);
    }


////////再按一般报价单程序来

    $_POST['flow_price'] = $_POST['flow_price']+0;//物流费
    $_POST['total_price'] = $_POST['total_price']+0;//总价格

    if(!$quotation->_validate($_POST)) { // 如果数据检验没通过,报错退出.

            $msg = implode('/r/n',$subinquiry->getErr());
            $errcode = implode('/r/n',$subinquiry->getErrCode());
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
            $response = ch_json_encode($response);
            exit($response);
    }

    // 自动过滤
    $data = $quotation->_facade($_POST);
    // 自动填充
    $data = $quotation->_autoFill($data);
    unset($data['quotation_id']);
    $data['comet'] = isset($_POST['quotation_comet'])?$_POST['quotation_comet']:'';
   
    //先修改数据报价单表
    if($quotation->update($data,$quotation_id) === false) {
        $msg = '修改报价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4125));
        $response = ch_json_encode($response);
        exit($response);
    }
    //清除掉原来报价单商品表的该报价单的商品数据
    if(!$quotation->delgoods($quotation_id)){
        $msg = '修改报价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4121));
        $response = ch_json_encode($response);
        exit($response);
    }


    /*
    要把报价单的商品写入数据库
    1个报价单里有N个商品,我们可以循环写入报价单对应的商品表ecs_temp_quotegoods表
    */
    // 接收报价单中所有的商品
    //验证产品信息
    if(!isset($_POST['goods'])){
        $data = array("success"=>"false","error"=>array("msg"=>'至少有一种产品','code'=>4800));
        $data = ch_json_encode($data);
        exit($data); 
        
    }
    
    $items = $_POST['goods']; // 商品信息
    
    $cnt = 0;  // cnt用来记录插入ecs_temp_quotegoods成功的次数

    $quotegoods = new QuotegoodsModel(); // 获取ecs_temp_quotegoods的操作model
 
    foreach($items as $k=>$goodsvalue) {  // 循环订单中的商品,写入ecs_temp_quotegoods表

        $goodsvalue['requestgoods_id'] = $goodsvalue['requestgoods_id']+0;//询价单商品ID
      
        $goodsvalue['is_attach'] = $goodsvalue['is_attach']+0;//商品是否有附件
        $goodsvalue['goods_price'] = $goodsvalue['goods_price']+0;//商品单价
       
        if(!$quotegoods->_validate($goodsvalue)) {  // 如果数据检验没通过,报错退出.
           
            $msg = implode('/r/n',$quotegoods->getErr());
            $errcode = implode('/r/n',$quotegoods->getErrCode());
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
            $response = ch_json_encode($response);
            exit($response);
        }


        $goodsvalue['quotation_id'] = $quotation_id;
        $goodsvalue['suppliers_id'] = $_SESSION['temp_buyers_id'];

            // 自动过滤
            $goodsvalue = $quotegoods->_facade($goodsvalue);

  
        if($quotegoods->add($goodsvalue)) {
            $cnt += 1;  // 插入一条ecs_temp_quotegoods成功,$cnt+1.
            // 因为,1个报价单有N条商品,必须N条商品,都插入成功,才算报价单插入成功!
         }
    }
    if(count($items) !== $cnt) { // 并没有全部入库成功.
        // 撤消此报价单
        $quotation->invoke($quotation_id);
        $subinquiry->invoke($request_id);
        $msg = '修改报价单失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4125));
        $response = ch_json_encode($response);
        exit($response);
    }


//调用SubinquiryModel的一个方法取出
$items = $quotation->quotationinfo($quotation_id,$_SESSION['temp_buyers_id']);
if(empty($items)){
$jobj=new stdclass();
$response = json_encode(array('success'=>'true','data'=>$jobj));
exit($response);
}

//商品信息
$items = $quotation->getQuotationGoods($items);
//附件信息
$items = $quotation->getRequestGoodsattch($items);

//返回数据给APP


    $response = array('success'=>'true','data'=>$items[0]);
    $response = ch_json_encode($response);
    exit($response);


}
?>