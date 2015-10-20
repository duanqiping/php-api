<?php
define('ACC',true);
require('./include/init.php');

$model = new Model();
$model->is_login();
// 设置一个动作参数,判断用户想干什么,下订单 done，修改订单 updata ,查看订单详情 select ，查看所有的订单 all,
$act = isset($_POST['act'])?$_POST['act']:'done';
if($act == 'done'){//订单入库
    //获取订单实例
    $data = $_POST;
    $purchase = new PurchaseModel();

    //自动验证
    $data['quotation_id'] = isset($_POST['quotation_id'])? $_POST['quotation_id']+0: 0;
    if(!$data['quotation_id']){
        $msg = 'quotation_id必须存在';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4800));
        $response = ch_json_encode($response);
        exit($response);
    }
    if(!$purchase->_validate($data)) { // 如果数据检验没通过,报错退出.
            $msg = implode('/r/n',$purchase->getErr());
            $errcode = implode('/r/n',$purchase->getErrCode());
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
            $response = ch_json_encode($response);
            exit($response);
    }
    $data['quotation_id'] = $data['quotation_id']+0;
    //一个报价单只能生成一个订单
    if($purchase->is_doneorder($data['quotation_id'])>0){
            $msg = '不能重复下单';
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4128));
            $response = ch_json_encode($response);
            exit($response);

    }
    //根据传来的报价单id查出订单信息
    $quotationinfo = new QuotationModel();
    $items = $quotationinfo->quotationinfo($data['quotation_id'] ,$_SESSION['temp_buyers_id']);
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
    $data['temp_purchase_sn'] = $purchase->orderSn();//订单号
    $data['buyers_id'] = $_SESSION['temp_buyers_id'];
    $data['suppliers_id'] = $items['suppliers_id'];
    //要根据卖家ID去查
    $user = new UserModel;
    $supliersinfo = $user->find($data['suppliers_id']);
    $data['suppliers_name'] =  $supliersinfo['nick'];
    $data['money'] = $items['total_price'];
    $data['name'] = $items['addressinfo']['name'];
    $data['mobile'] = $items['addressinfo']['mobile'];
    $data['address'] = $items['addressinfo']['address'];
    $data['state'] = 1;//订单状态0已取消1待付款2已付款-》申请退款-》进行退款中-》退款完成（已付款验证 2-》3， 2-》6）3卖家已确定，已发货 5申请退款中 6进行退款中9买家未确认订单
    $data['description'] = $items['comet'];
    $data['receive_time'] = $items['recieve_time'];
    //$data['method'] = $data['method']+0;
    $data['transportation'] = $items['flow_price'];
    $data['temp_buyers_address_id'] = $items['addressinfo']['temp_buyers_address_id'];
   /* if(isset($data['bank_id'])){
        if(!$purchase->isinbankid($data['bank_id'])){
            $msg = '本网站暂时不支持此银行支付方式';
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4127));
            $response = ch_json_encode($response);
            exit($response);

        }
        $data['bank_name'] = $purchase->getbankname($data['bank_id']);
    }*/
    $data['purchase_title'] =  $items['goods'][0]['goods_name'];
    //自动过滤
    $data = $purchase->_facade($data);
    // 自动填充
    $data = $purchase->_autoFill($data);
    //把数据写入ecs_temp_purchase表

    if(!$purchase->add($data)) {
        $msg = '订单入库失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4126));
        $response = ch_json_encode($response);
        exit($response);
    }
    // 获取刚刚产生的报价单的quotation_id值
    $temp_purchase_id = $purchase->insert_id();

//把商品写入订单商品表
  /*
    要把报价单的商品写入数据库
    1个报价单里有N个商品,我们可以循环写入报价单对应的商品表ecs_temp_quotegoods表
    */
    // 接收报价单中所有的商品
  
    $goodsitems = $items['goods']; // 商品信息
    $cnt = 0;  // cnt用来记录插入ecs_temp_quotegoods成功的次数

    $PurchaseGoods = new PurchaseGoodsModel(); // 获取PurchaseGoodsModel实例
  
    foreach($goodsitems as $k=>$goodsvalue) {  // 循环订单中的商品,写入eecs_temp_purchase_goods表
        $goodsvalue['temp_purchase_id'] = $temp_purchase_id ;
        $goodsvalue['version'] = $goodsvalue['goods_version'];
        $goodsvalue['amount'] = $goodsvalue['goods_account'];
        $goodsvalue['unit'] = $goodsvalue['goods_unit'];
        $goodsvalue['price'] = $goodsvalue['goods_price'];
        $goodsvalue['description'] = $goodsvalue['comet'];
        $goodsvalue['goods_id'] = -1;
        $goodsvalue['name'] = $goodsvalue['goods_name'];
        // 自动过滤
        $goodsvalue = $PurchaseGoods->_facade($goodsvalue);

        if($PurchaseGoods->add($goodsvalue)) {
            $cnt += 1;  // 插入一条ecs_temp_quotegoods成功,$cnt+1.
            // 因为,1个报价单有N条商品,必须N条商品,都插入成功,才算报价单插入成功!
         }
}
    if(count($goodsitems) !== $cnt) { // 并没有全部入库成功.
        // 撤消此报价单
        $purchase->invoke($temp_purchase_id);
        $msg = '订单入库失败';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4126));
        $response = ch_json_encode($response);
        exit($response);
    }
  //订单生成功， $temp_purchase_id返回订单详情

    //接收订单ID
     $order_id = $temp_purchase_id;
     //调用SubinquiryModel的一个方法取出
    $items = $purchase->purchaseinfo($_SESSION['temp_buyers_id'],$order_id);
    if(empty($items)){
        $jobj=new stdclass();
        $response = json_encode(array('success'=>'true','data'=>$jobj));
        exit($response);
    }

    //返回数据给APP
    $response = array('success'=>'true','data'=>$items[0]);
    $response = ch_json_encode($response);
    exit($response);

}else if($act == 'changepay'){//买家修改订单支付方式
    //获取订单实例
    $data['temp_purchase_id'] = isset($_POST['temp_purchase_id'])? $_POST['temp_purchase_id']+0: 0;
    if(!$data['temp_purchase_id']){
        $msg = 'temp_purchase_id必须存在';
        $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4800));
        $response = ch_json_encode($response);
        exit($response);
    }
    
    $purchase = new PurchaseModel();

    if(!$purchase->_validate($data)) { // 如果数据检验没通过,报错退出.
            $msg = implode('/r/n',$purchase->getErr());
            $errcode = implode('/r/n',$purchase->getErrCode());
            $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>$errcode));
            $response = ch_json_encode($response);
            exit($response);
    }
    //自动过滤
   $data = $purchase->_facade($data);
   $uid = $_SESSION['temp_buyers_id'];
   if($data['method'] == 0){
      $data['bank_id'] = '';
      $data['bbank_name'] = '';
   }else if($data['method'] == 2){
        if(isset($data['bank_id'])){
            if(!$purchase->isinbankid($data['bank_id'])){
                $msg = '本网站暂时不支持此银行支付方式';
                $response = array("success"=>"false","error"=>array("msg"=>$msg,'code'=>4127));
                $response = ch_json_encode($response);
                exit($response);

            }
             $data['bank_name'] = $purchase->getbankname($data['bank_id']);
        }


   }
   $purchase->chage_pay($data,$data['temp_purchase_id'],$uid);

    $response = array("success"=>"true","data"=>array("msg"=>'订单支付方式选择成功'));
    $response = ch_json_encode($response);
    exit($response);


    
} else if ($act == 'orderlist'){//订单列表
    //接收订单状态码
    $state = isset($_POST['state']) ? $_POST['state']+0 :1;//默认待付款
    if($state == 0){
            $data = array("success"=>"false","error"=>array("msg"=>'订单已经取消，不能查看','code'=>4800));
            $data = ch_json_encode($data);
            exit($data);

    }

    $type = isset($_POST['type']) ? $_POST['type']+0 :1;//0卖家1买家

    //接收页码
    $page = !isset($_POST['page'])? 1 : $_POST['page']+0;
    //每页显示多少条
    $limit = !isset($_POST['limit'])?5:$_POST['limit']+0;
    
    $purchase = new PurchaseModel();
    $uid = $_SESSION['temp_buyers_id'];
    $arr = $purchase->orderlist($state,$uid,$type,$page,$limit);
    //取订单产品信息
    $items = $purchase->getPurchaseGoods($arr);
    if(empty($items)){
    $response = json_encode(array('success'=>'true','data'=>array()));
    exit($response);
    }
  
    $response = array('success'=>'true','data'=>$items);
    $response = ch_json_encode($response);
    exit($response);
}else if($act == 'orderinfo'){//查看订单详情,只能看自己的订单详情
    //接收订单ID
    if(isset($_POST['temp_purchase_id'])&&$_POST['temp_purchase_id']!=0){
         $order_id = $_POST['temp_purchase_id']+0;
         $action = 'purchase';
    }else if(isset($_POST['quotation_id'])&&$_POST['quotation_id']!=0){
         $order_id = $_POST['quotation_id']+0;
         $action = 'quotation';
    }else{            
        $data = array("success"=>"false","error"=>array("msg"=>'temp_purchase_id或quotation_id不存在','code'=>4800));
        $data = ch_json_encode($data);
        exit($data);
        
    }
     

     $uid = $_SESSION['temp_buyers_id'];//登录人ID,分辨是报价还是问价人

     $purchase = new PurchaseModel();

     //调用SubinquiryModel的一个方法取出
    $items = $purchase->purchaseinfo($uid,$order_id,$action);
    if(empty($items)){
        $jobj=new stdclass();
        $response = json_encode(array('success'=>'true','data'=>$jobj));
        exit($response);
    }
    $items = $items[0];
//判断来查看的是订单的报价人还是问价人
     if(isset($_POST['temp_purchase_id'])&&$_POST['temp_purchase_id']!=0){
         $shenfen = $purchase->find($_POST['temp_purchase_id']+0);
     }else if(isset($_POST['quotation_id'])&&$_POST['quotation_id']!=0){
         $shenfen = $purchase->userid($_POST['quotation_id']+0);
     }
     if($shenfen['buyers_id']== $_SESSION['temp_buyers_id']){//买家
              $items['shenfen'] = 1;

     }else if($shenfen['suppliers_id']== $_SESSION['temp_buyers_id']){//卖家
        //把订单is_read状态改为1
        $is_read = array('is_read'=>1);
        $purchase->update($is_read,$shenfen['temp_purchase_id']);
        $items['shenfen'] = 2;
     }
    //返回数据给APP
    $response = array('success'=>'true','data'=>$items);
    $response = ch_json_encode($response);
    exit($response);

}else if($act == 'click'){
//点击操作：买家点击确认付款：生成订单，状态变为1->待付款(可以取消订单/可以去支付)///点付款走付款流程。。///付款成功，订单状态为2->待发货（提醒发货/申请退款）///3->卖家已发货，买家待收货 ///状态码为4->买家收货订单已完成.///
        //订单ID，操作人ID
        $payment = new PaymentModel;
        $account = new AccountModel;
        $order_id = isset($_POST['temp_purchase_id']) ? intval($_POST['temp_purchase_id']) : 0;
        if(!$order_id){
            $data = array("success"=>"false","error"=>array("msg"=>'order_id错误','code'=>4800));
            $data = ch_json_encode($data);
            exit($data);

        }
        $uid = $_SESSION['temp_buyers_id'];
        $state = isset($_POST['state']) ? intval($_POST['state']) : false;
        if($state === false){
            $data = array("success"=>"false","error"=>array("msg"=>'状态码不能为空','code'=>4800));
            $data = ch_json_encode($data);
            exit($data);

        }
        //获取订单实例
        $purchase = new PurchaseModel();

        //判断能不能修改状态
        $sts = $purchase->is_changestates($uid,$order_id);
        $st = $sts['state']; //原来订单状态
        $purchase_sn = $sts['temp_purchase_sn']; //订单号
        $method =  $sts['method'];   //订单支付方式

        switch($state){//传过来的状态码
            case 0://取消订单，要在状态为1的时候买家取消订单
                if($st != 1){//必须在状态1的时候
                    $data = array("success"=>"false","error"=>array("msg"=>'此订单不可以取消','code'=>4800));
                    $data = ch_json_encode($data);
                    exit($data);
                }

            break;

            case 2://买家点取消退款
                if($st != 5){//必须在状态5
                    $data = array("success"=>"false","error"=>array("msg"=>'无权操作','code'=>4800));
                    $data = ch_json_encode($data);
                    exit($data);

                }

            break; 

            case 3://卖家点发货
                    if($st != 2){//订单必须为2
                    $data = array("success"=>"false","error"=>array("msg"=>'此订单买家必须支付了才能确认发货','code'=>4800));
                    $data = ch_json_encode($data);
                    exit($data); 
 
              }
            break;
            case 4://买家点收货
                    if($st != 3){//必须在3
                    $data = array("success"=>"false","error"=>array("msg"=>'此订单必须卖家发货了，才能确认收货','code'=>4800));
                    $data = ch_json_encode($data);
                    exit($data); 
                }
    
          
            break;
            case 5://买家点申请退款
                    if($st != 2 ){//必须为2
                    $data = array("success"=>"false","error"=>array("msg"=>'此订单不可以申请退款','code'=>4800));
                    $data = ch_json_encode($data);
                    exit($data); 
 
              }
            break;
            case 6://卖家点了同意退款
                    if($st != 5 ){//必须在5
                    $data = array("success"=>"false","error"=>array("msg"=>'此订单不可以操作退款','code'=>4800));
                    $data = ch_json_encode($data);
                    exit($data); 
                    
 
              }
            break;
 
            default:
                    $data = array("success"=>"false","error"=>array("msg"=>'你无此权限操作此订单','code'=>4800));
                    $data = ch_json_encode($data);
                    exit($data);    
            break; 
        }
        //根据状态修改订单状态
        if($state != 6){
            $updata = array('state'=>$state);
            $affrows = $purchase->updateorderstate($updata,$order_id,$uid);

            if($affrows){
                //查出修改后订单的信息
                 $firstRow = $purchase->find($order_id);


            }else{
                    $data = array("success"=>"false","error"=>array("msg"=>'状态修改失败','code'=>4129));
                    $data = ch_json_encode($data);
                    exit($data);   

            }
        }
    //发短息，业务逻辑
        $mobiles = $purchase->mobile($order_id);
        switch($state) {//传过来的状态码
            case 0://取消订单
            
            break;

            case 2://取消退款，后台做逻辑
               //代码。。。

            break;
            case 3:
                 //当卖家点击确认发货时，即传来的状态码为3时发短息给买家
                $message = '您的订单号（'.$firstRow['temp_purchase_sn'].'），卖家已发货，请及时查收。';
                $mobile = $mobiles['buyermobile'];
                sendmessage($mobile,$message);

            break;
            case 4://买家确认收货

                //买家确认收货，把账户缓存的钱转卖家账户余额里
                $sid = $firstRow['suppliers_id'];

                if(!$payment->tosuppliersaccount($firstRow['money'],$sid)){
                    //把状态修改回来
                    $updata = array('state'=>3);
                    $affrows = $purchase->updateorderstate($updata,$order_id,$uid);

                    $data = array("success"=>"false","error"=>array("msg"=>'状态修改失败','code'=>4129));
                    $data = ch_json_encode($data);
                    exit($data);
                    
                }  
                 //在订单表填上完成时间
                $finish_time = array('finish_time'=>time());
                $purchase->updateorderstate($finish_time,$order_id,$uid);  
            break;
            case 5://买家申请退款
            break;
            case 6:
                //卖家同意买家可以退款
                 //在payment增加数据
                //查此订单有没有在数据库插入过数据
                $row = $payment->selectpaymentall($purchase_sn);
                if( !$row ){//不可以申请退款，因为账户没有记录付过钱
                    $data = array("success"=>"false","error"=>array("msg"=>'此订单没有交易，不可以申请退款','code'=>4130));
                    $data = ch_json_encode($data);
                    exit($data);
                     
                 }
                 if( $row['type'] ==3 ){
                    $data = array("success"=>"false","error"=>array("msg"=>'申请退款已经有记录','code'=>1));
                    $data = ch_json_encode($data);
                    exit($data);

                 }
                 //修改订单的状态
                 $six = array('state'=>6);
                 $afr = $purchase->updateorderstate($six,$order_id,$uid);
                 
                if(!$afr){
                 $data = array("success"=>"false","error"=>array("msg"=>'状态修改失败','code'=>4129));
                 $data = ch_json_encode($data);
                 exit($data);               

                }
                //获取订单信息
                $rows = $purchase->lookpurchase($purchase_sn);
                //没有插入过，用事务

                //判断买家是否有账户在acount
                $yaccount = $account->is_account($rows['buyers_id']);

                if($yaccount <= 0 ){//说明没有账户，就帮创建
                    $accountdata = array(
                        'temp_buyers_id'=>$rows['buyers_id'],
                        'total'=>0.00,
                        'withdraw'=>0.00

                        );
                    if($account->add($accoundata)){
                        if($account->insert_id()<0){
                        //把状态修改回来
                        $updata = array('state'=>5);
                        $affrows = $purchase->updateorderstate($updata,$order_id,$uid);
                        $data = array("success"=>"false","erro"=>array("msg"=>'买家账号绑定失败','code'=>4131));
                        $data = ch_json_encode($data);
                        exit($data); 

                        }


                    }
	                     
                 }
                //在payment把原来的订单入账信息修改为一条退款信息，type=3，同时在卖家count里的缓存减去这笔钱，在买家的count账户的缓存加上这笔钱， 在acount插入一条数据,事务
               if($payment->refund($rows['suppliers_id'],$rows['buyers_id'],$purchase_sn,$rows['money'])){
                    $response = array("success"=>"true","data"=>array("msg"=>'申请退款成功'));
                    $response = ch_json_encode($response);
                    exit($response);

               }else{
                    //把状态修改回来
                    $updata = array('state'=>5);
                    $affrows = $purchase->updateorderstate($updata,$order_id,$uid);
                    $data = array("success"=>"false","error"=>array("msg"=>'退款申请失败','code'=>4132));
                    $data = ch_json_encode($data);
                    exit($data);

               }
                     
            break;
            case 7:
            break;
      
        }
        $response = array("success"=>"true","data"=>array("msg"=>'订单状态已经修改成功'));
        $response = ch_json_encode($response);
        exit($response);



}else if ($act == 'accmoney'){//账户余额
    $uid = $_SESSION['temp_buyers_id'];
    $account = new AccountModel;
    //判断是否有账户
    if(!$account->is_account($uid)){
         $response = array('success'=>'true','data'=>array('total'=>0));
         $response = ch_json_encode($response);
         exit($response);

    }
    $count = $account->showmoney($uid);

    if($count>=0){
         $response = array('success'=>'true','data'=>array('total'=>$count));
         $response = ch_json_encode($response);
         exit($response);

    }else{
         $response = array('success'=>'true','data'=>array('total'=>0));
         $response = ch_json_encode($response);
         exit($response);

    }

 
}else if($act == 'acc'){//查看账户信息account 收支明细
    $payment = new PaymentModel;

    //type=0查看全部，1 收入(做为报价人，下的订单的已经入账金额) 2支出（提现）
    $type = isset($_POST['type']) ? $_POST['type']+0 : 0;
    $uid = $_SESSION['temp_buyers_id'];

    //接收页码
    $page = !isset($_POST['page'])||empty($_POST['page']) ? 1 : $_POST['page']+0;
    //每页显示多少条
    $limit = empty($_POST['limit'])?5:$_POST['limit']+0;
    $arr = $payment->payment_details($page,$limit,$type,$uid);

    if(empty($arr)){
    $response = json_encode(array('success'=>'true','data'=>array()));
    exit($response); 

    }

    //返回JSON
    $response = array('success'=>'true','data'=>$arr);
    $response = ch_json_encode($response);
    exit($response);



}else if($act == 'binding'){//绑定账户
    //先判断该账户是否已经存在
    $buyersaccount = new BuyersAccountModel;

    $total = $buyersaccount->is_buyersaccount($_SESSION['temp_buyers_id']);

    
    if($total && !isset($_POST['alipay']) && !isset($_POST['person'])){//查看账户信息

        //查找用户是否已经绑定了账户，返回账户信息
        //查看
        $data = $buyersaccount->selectmyaccount($_SESSION['temp_buyers_id']);

        $response = array('success'=>'true','data'=>$data);
        $response = ch_json_encode($response);
        exit($response);

   }else if(!$total && !isset($_POST['alipay']) && !isset($_POST['person'])){
            $data = array("success"=>"false","error"=>array("msg"=>'账户不存在，请先绑定','code'=>4134));
            $data = ch_json_encode($data);
            exit($data); 
   }else if(isset($_POST['alipay']) && isset($_POST['person'])){//绑定账号
        //接收卡号和开户名
        //自动验证
        $data = $payment->_validate($_POST);

        //手工验证是否是支付宝账号必须为手机或邮箱
        if(!preg_match('/^1[358]\d{9}$/', $_POST['alipay']) && !preg_match('/^[_.0-9a-z-a-z-]+@([0-9a-z][0-9a-z-]+.)+[a-z]{2,4}$/',$_POST['alipay'])){
            $data = array("success"=>"false","error"=>array("msg"=>'请正确填写支付宝账号','code'=>4135));
            $data = ch_json_encode($data);
            exit($data);
        }
        //自动过滤
        $data = $buyersaccount->_facade($data);
        //自动填充
        $data = $buyersaccount->_autoFill($data);
        if($total){
                //存在，修改该账户信息
                $buyersaccount->updatemyaccount($data,$_SESSION['temp_buyers_id']);
                //返回修改后的数据
                $data = $buyersaccount->selectmyaccount($_SESSION['temp_buyers_id']);

                $response = array('success'=>'true','data'=>$data);
                $response = ch_json_encode($response);
                exit($response);


        }else{//为用户新增一条数据

                    //不存在，插入数据（目前我司只支持支付宝）
                   
                    $data['temp_buyers_id'] = $_SESSION['temp_buyers_id']+0;
                    if($buyersaccount->add($data)){
                        if($buyersaccount->insert_id()>0){
                            //返回新增账户信息
                        $data = $buyersaccount->selectmyaccount($_SESSION['temp_buyers_id']);

                        $response = array('success'=>'true','data'=>$data);
                        $response = ch_json_encode($response);
                        exit($response);

                        }

                    }

                        $data = array("success"=>"false","error"=>array("msg"=>'账号绑定失败','code'=>4136));
                        $data = ch_json_encode($data);
                        exit($data); 
           
                  
        }
    }
}else if($act == 'application'){//申请提现
    //接收收款单位
    //接收提现金额
    //接收卡号
    //接收开户名
 //先判断该账户是否已经存在
    $buyersaccount = new BuyersAccountModel;

    $total = $buyersaccount->is_buyersaccount($_SESSION['temp_buyers_id']);

        //接收卡号和开户名
        //自动验证
        $data = $payment->_validate($_POST);
        //验证是否是银行的卡号
        
        
        //手工验证是否是支付宝账号必须为手机或邮箱
        if((bankInfo($_POST['alipay']))&&!preg_match('/^1[358]\d{9}$/', $_POST['alipay']) && !preg_match('/^[_.0-9a-z-a-z-]+@([0-9a-z][0-9a-z-]+.)+[a-z]{2,4}$/',$_POST['alipay'])){
            $data = array("success"=>"false","error"=>array("msg"=>'请正确填写银行账号','code'=>4135));
            $data = ch_json_encode($data);
            exit($data);
        }
        //自动过滤
        $data = $buyersaccount->_facade($data);
        //自动填充
        $data = $buyersaccount->_autoFill($data);
        if($total){
                //存在，修改该账户信息
                $buyersaccount->updatemyaccount($data,$_SESSION['temp_buyers_id']);
                //返回修改后的数据
                $data = $buyersaccount->selectmyaccount($_SESSION['temp_buyers_id']);

                $response = array('success'=>'true','data'=>$data);
                $response = ch_json_encode($response);
                exit($response);


        }else{//为用户新增一条数据

                    //不存在，插入数据（目前我司只支持支付宝）
                   
                    $data['temp_buyers_id'] = $_SESSION['temp_buyers_id']+0;
                    if($buyersaccount->add($data)){
                        if($buyersaccount->insert_id()>0){
                            //返回新增账户信息
                        $data = $buyersaccount->selectmyaccount($_SESSION['temp_buyers_id']);

                        $response = array('success'=>'true','data'=>$data);
                        $response = ch_json_encode($response);
                        exit($response);

                        }

                    }

                        $data = array("success"=>"false","error"=>array("msg"=>'账号绑定失败','code'=>4136));
                        $data = ch_json_encode($data);
                        exit($data); 
           
                  
        }

    //申请提现要判断余额是否足够
    $account = new AccountModel;
    $total = $account->showmoney($_SESSION['temp_buyers_id']);
    $money = $_POST["money"]+0;
    if($total<$money){
        $data = array("success"=>"false","error"=>array("msg"=>'余额不足','code'=>4137));
        $data = ch_json_encode($data);
        exit($data); 
    }
    if($account->applycash($_SESSION['temp_buyers_mobile'],$data['alipay'],$_SESSION['temp_buyers_id'],$money)){
        $response = array("success"=>"true","data"=>array("msg"=>'提现申请成功'));
        $response = ch_json_encode($response);
        exit($response);

    }else{
        $data = array("success"=>"false","error"=>array("msg"=>'提现申请失败','code'=>4138));
        $data = ch_json_encode($data);
        exit($data); 
    }
   
}else if($act == 'payment'){//提现信息
    $payment = new PaymentModel;

    $uid = $_SESSION['temp_buyers_id'];

    //接收页码
    $page = !isset($_POST['page'])||empty($_POST['page']) ? 1 : $_POST['page']+0;
    //每页显示多少条
    $limit = empty($_POST['limit'])?5:$_POST['limit']+0;
    $arr = $payment->payment_details($page,$limit,2,$uid);

    if(empty($arr)){
    $response = json_encode(array('success'=>'true','data'=>array()));
    exit($response); 

    }

    //返回JSON
    $response = array('success'=>'true','data'=>$arr);
    $response = ch_json_encode($response);
    exit($response);


}else if($act == 'remind'){//买家提醒卖家发货
    //获取订单实例
    $purchase = new PurchaseModel;
    //获取买家和卖家电话
    $mobiles = $purchase->mobile($order_id);
    //接收订单ID
    $order_id = $_POST['order_id']+0;
    //从session取到买家的ID
    $uid = $_SESSION['temp_buyers_id'];

    //判断订单状态如果一直是2才可以点击此按钮提醒卖家

    $firstRow =  $purchase ->is_changestates($uid,$order_id);

    if($firstRow['state'] == 2 ){
    $message = '订单号（'.$firstRow['temp_purchase_sn'].'），买家已付款，请及时发货。';
    $mobile = $mobiles['buyermobile'];
    sendmessage($mobile,$message);
    $response = array("success"=>"true","data"=>array("msg"=>'提醒卖家发货成功'));
    $response = ch_json_encode($response);
    exit($response);
    }else{
        $data = array("success"=>"false","error"=>array("msg"=>'提醒卖家发货失败','code'=>4128));
        $data = ch_json_encode($data);
        exit($data); 
    }

}else if($act == 'banklist'){

 $bankID =  array(
                'ICBCB2C'=>'中国工商银行',
                'ABC'=>'中国农业银行',
                'CCB'=>'中国建设银行',
                'SPDB'=>'浦发银行',
                'BOCB2C'=>'中国银行',
                'CMB'=>'招商银行',
                'CIB'=>'兴业银行',
                'GDB'=>'广发银行',
                'CMBC'=>'中国民生银行',
                'HZCBB2C'=>'杭州银行',
                'CEB-DEBIT'=>'中国光大银行',
                'SHBANK'=>'上海银行',
                'NBBANK'=>'宁波银行',
                'SPABANK'=>'平安银行',
                'BJRCB'=>'北京农商银行',
                'FDB'=>'富滇银行',
                'POSTGC'=>'中国邮政储蓄银行',
                'COMM'=>'交通银行',
                'BJBANK'=>'北京银行',
                'SHRCB'=>'上海农商银行',
                'WZCBB2C-DEBIT'=>'温州银行',
                'CITIC-DEBIT'=>'中信银行'
                 );
    $bank = array();
   foreach ($bankID as $key => $value) {
         $bank[ ] = array('bank_id'=>$key,'bank_name'=>$value,'bank_icon'=>NROOT . '/AskPriceApi/data/images/bank_icon/'.$key.'.png');
   }
    $response = array('success'=>'true','data'=>$bank);
    $response = ch_json_encode($response);
    exit($response);
}

?>