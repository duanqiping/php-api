<?php
defined('ACC')||exit('Acc Deined');
class PurchaseModel extends Model {
protected $table = 'ecs_temp_purchase';
    protected $pk = 'temp_purchase_id';
    protected $fields = array('temp_purchase_id','temp_purchase_sn','temp_inquiry_id','buyers_id','suppliers_id','suppliers_name','suppliers_alipay','time','money','name','mobile','address','state','description','receive_time','finish_time','method','quotation_id','transportation','temp_buyers_address_id','bank_id','bank_name','purchase_title','is_read');

    protected $_valid = array(

                           // array('method',1,'必须先支付方式','4800','in','0,2'), //代表0支付宝2网银
                            array('state',0,'必须0—7的数字','4800','in','0,1,2,3,4,5,6,7')
    );

    protected $_auto = array(

                            array('time','function','time')
                            );

				protected $bankID =  array(
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
public function isinbankid($key){

	return array_key_exists($key, $this->bankID);
}
public function getbankname($key){
	return $this->bankID[$key];

}
public function invoke($temp_purchase_id) {
        $this->delete($temp_purchase_id); // 先删掉订单
        $sql = 'delete from ecs_temp_purchase_goods where $temp_purchase_id = ' .$temp_purchase_id; // 再删订单对应的商品

        if($this->db->query($sql)){

                return 1;
        }else{
                return 0;
        }
        
    }
//判断是否重复下单
 public function is_doneorder($quotation_id){
 	 $sql = 'select count(*) from '.$this->table.' where quotation_id ='.$quotation_id;
 	 return $this->db->getOne($sql);

 }
 //修改订单支付方式
 public function chage_pay($data,$id,$uid){

        $rs = $this->db->autoExecute($this->table,$data,'update',' where '.$this->pk.'='.$id.' and buyers_id ='.$uid);
        if($rs) {
            return $this->db->affected_rows();
        } else {
            return false;
        }
 }
//订单列表
 public function orderlist($state,$uid,$type,$page=1,$limit=5){
  
  //统计页数
    if($type == 1){//买家
        if($state == 5){
          $sql = 'select count(*) from ecs_temp_purchase where state in(5,6,7) and buyers_id = '.$uid;
        }else{
          $sql = 'select count(*) from ecs_temp_purchase where state ='.$state.' and buyers_id = '.$uid;
        }
        
    }else{//卖家
        if($state == 5){
          $sql = 'select count(*) from ecs_temp_purchase where state in(5,6,7) and suppliers_id = '.$uid;
        }else{
           $sql = 'select count(*) from ecs_temp_purchase where state ='.$state.' and suppliers_id = '.$uid;
        }
        
    }

    //总条数

    $total = $this->db->getOne($sql);

    //总页数
    $totalpage = ceil($total/$limit);
    //偏移量
    $offset = ($page-1)*$limit;

    if(($page<1)||($page>$totalpage)){
        $page = 1;
    }

    //取数据
    if($type == 1){//买家
      if($state == 5){
        $sql = 'select * from ecs_temp_purchase  where state in(5,6,7) and buyers_id = '.$uid .' order by time desc limit '.$offset.','.$limit;
      }else{
        $sql = 'select * from ecs_temp_purchase  where state ='.$state.' and buyers_id = '.$uid .' order by time desc limit '.$offset.','.$limit;
      }
      

    }else{//卖家
        if($state == 5){
          $sql = 'select ecs_temp_purchase.*,ecs_temp_buyers.nick from ecs_temp_purchase left join ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_purchase.buyers_id where state in(5,6,7) and suppliers_id = '.$uid.' order by time desc limit '.$offset.','.$limit;
        }else{
          $sql = 'select ecs_temp_purchase.*,ecs_temp_buyers.nick from ecs_temp_purchase left join ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_purchase.buyers_id where state ='.$state.' and suppliers_id = '.$uid.' order by time desc limit '.$offset.','.$limit;
        }
        

    }

    $rs = $this->db->query($sql);

       
    $arr = array();
    $i = 0;
    while($row = mysql_fetch_assoc($rs)){
        $info['temp_purchase_id'] = $row['temp_purchase_id'];
        $info['temp_purchase_sn'] = $row['temp_purchase_sn'];
        $info['buyersinfo']['temp_buyers_id'] = $row['buyers_id'];
        $info['buyersinfo']['nick'] = isset($row['nick'])?$row['nick']:'';
        $info['suppliersinfo']['temp_buyers_id'] = $row['suppliers_id'];
        $info['suppliersinfo']['nick'] = $row['suppliers_name'];
        $info['time'] = $row['time'];
        $info['money'] = $row['money'];
        $info['transportation'] = $row['transportation'];
        $info['method'] = $row['method'];
        $info['bank_id'] = $row['bank_id'];
        $info['bank_name'] = $row['bank_name'];
        $info['state'] = $row['state'];
        $info['receive_time'] = $row['receive_time'];
        $info['comet'] = $row['description'];
        $info['addressinfo']['temp_buyers_address_id'] = $row['temp_buyers_address_id'];
        $info['addressinfo']['name'] = $row['name'];
        $info['addressinfo']['address'] = $row['address'];
        $info['addressinfo']['mobile'] = $row['mobile'];
        $arr[$i++] = $info;
    }

    return $arr;
 }
//获取订单产品信息
public function getPurchaseGoods($arr){

 //取产品信息
    foreach ($arr as $k=>$v){
        $sql = 'select temp_purchase_goods_id,name as goods_name,version as goods_version,amount as goods_account,unit as goods_unit,price as goods_price from ecs_temp_purchase_goods where temp_purchase_id ='.$v['temp_purchase_id'];
        $row = $this->db->getAll($sql);
        if(!empty($row)){
          $arr[$k]['goods'] = $row;
        }
        


    }

      return $arr;

}
//查看订单详情
public function purchaseinfo($uid,$order_id,$action='purchase'){

    //根据订单ID取出订单详细信息
    if($action == 'purchase'){
      $sql = 'select ecs_temp_purchase.temp_purchase_id,ecs_temp_purchase.temp_purchase_sn,ecs_temp_purchase.temp_inquiry_id,ecs_temp_purchase.buyers_id,ecs_temp_purchase.suppliers_id,ecs_temp_purchase.suppliers_name,ecs_temp_purchase.suppliers_alipay,ecs_temp_purchase.time,ecs_temp_purchase.money,ecs_temp_purchase.name,ecs_temp_purchase.mobile,ecs_temp_purchase.address,ecs_temp_purchase.state,ecs_temp_purchase.description as comet,ecs_temp_purchase.receive_time,ecs_temp_purchase.finish_time,ecs_temp_purchase.method,ecs_temp_purchase.quotation_id,ecs_temp_purchase.transportation as flow_price,ecs_temp_purchase.temp_buyers_address_id,ecs_temp_purchase.bank_id,ecs_temp_purchase.bank_name,ecs_temp_purchase.purchase_title,';
      $sql .= 'ecs_temp_purchase_goods.temp_purchase_goods_id,ecs_temp_purchase_goods.version,ecs_temp_purchase_goods.amount,ecs_temp_purchase_goods.unit,ecs_temp_purchase_goods.price,ecs_temp_purchase_goods.name as goods_name from ecs_temp_purchase left join ecs_temp_purchase_goods on ecs_temp_purchase.temp_purchase_id = ecs_temp_purchase_goods.temp_purchase_id where ecs_temp_purchase.temp_purchase_id = '.$order_id.' and (ecs_temp_purchase.buyers_id = '.$uid .' or ecs_temp_purchase.suppliers_id = '.$uid.')';

    }else{
      $sql = 'select ecs_temp_purchase.temp_purchase_id,ecs_temp_purchase.temp_purchase_sn,ecs_temp_purchase.temp_inquiry_id,ecs_temp_purchase.buyers_id,ecs_temp_purchase.suppliers_id,ecs_temp_purchase.suppliers_name,ecs_temp_purchase.suppliers_alipay,ecs_temp_purchase.time,ecs_temp_purchase.money,ecs_temp_purchase.name,ecs_temp_purchase.mobile,ecs_temp_purchase.address,ecs_temp_purchase.state,ecs_temp_purchase.description as comet,ecs_temp_purchase.receive_time,ecs_temp_purchase.finish_time,ecs_temp_purchase.method,ecs_temp_purchase.quotation_id,ecs_temp_purchase.transportation as flow_price,ecs_temp_purchase.temp_buyers_address_id,ecs_temp_purchase.bank_id,ecs_temp_purchase.bank_name,ecs_temp_purchase.purchase_title,';
      $sql .= 'ecs_temp_purchase_goods.temp_purchase_goods_id,ecs_temp_purchase_goods.version,ecs_temp_purchase_goods.amount,ecs_temp_purchase_goods.unit,ecs_temp_purchase_goods.price from ecs_temp_purchase left join ecs_temp_purchase_goods on ecs_temp_purchase.temp_purchase_id = ecs_temp_purchase_goods.temp_purchase_id where ecs_temp_purchase.quotation_id  = '.$order_id.' and (ecs_temp_purchase.buyers_id = '.$uid .' or ecs_temp_purchase.suppliers_id = '.$uid.')';
    }
    
 
  
     $rs = $this->db->query($sql);


     $arr = array();
     
     while($row = mysql_fetch_assoc($rs)){
        $arr[] = $row;
     }

      $goods_info = array();
    
      foreach($arr as $k=>$v){
             $arr[$k]['addressinfo']['temp_buyers_address_id']= $v['temp_buyers_address_id'];
             $arr[$k]['addressinfo']['address']= $v['address'];
             $arr[$k]['addressinfo']['name'] = $v['name'];
             $arr[$k]['addressinfo']['mobile']= $v['mobile'];

             $goods_info[] = array(
                          'temp_purchase_goods_id'=>$v['temp_purchase_goods_id'],
                          'goods_version'=>$v['version'],
                          'goods_account'=>$v['amount'],
                          'goods_unit'=>$v['unit'],
                          'goods_name'=>$v['goods_name'],
                          'goods_price'=>$v['price']
                          );

        $arr[$k]['goods'] = $goods_info;


      }
        unset($arr[0]['temp_buyers_address_id']);
        unset($arr[0]['address']);
        unset($arr[0]['mobile']);
        unset($arr[0]['price']);
        unset($arr[0]['temp_purchase_goods_id']);
        unset($arr[0]['version']);
        unset($arr[0]['amount']);
        unset($arr[0]['unit']);
     return  $arr;
}
//判断能不能修改状态
public function is_changestates($uid,$order_id){
    $sql = 'select state,temp_purchase_sn,method from ecs_temp_purchase  where temp_purchase_id = '.$order_id.' and (buyers_id = '.$uid .' or suppliers_id = '.$uid.')';
    return $this->db->getRow($sql);

}
//修改订单状态
public function updateorderstate($data,$order_id,$uid){
        $rs = $this->db->autoExecute($this->table,$data,'update',' where temp_purchase_id = '.$order_id.' and (buyers_id = '.$uid .' or suppliers_id = '.$uid.')');
        if($rs) {
            return $this->db->affected_rows();
        } else {
            return false;
        }

}
//根据订单号查得买家和卖家手机号码
public function mobile($order_id){
  $sql = 'select ecs_temp_buyers.temp_buyers_mobile as buyermobile from ecs_temp_purchase left join ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_purchase.buyers_id where temp_purchase_id ='.$order_id;
  $sql .= ' union all select ecs_temp_buyers.temp_buyers_mobile as suppliermobile from ecs_temp_purchase left join ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_purchase.suppliers_id  where temp_purchase_id ='.$order_id;
  return $this->db->getRow($sql);
}
//生成订单号
public function orderSn() {
    $sn = date('ymdHis').str_pad($_SESSION['temp_buyers_id'],6,"0",STR_PAD_LEFT).substr(microtime(),2,4);

    $sql = 'select count(*) from ' . $this->table  . ' where temp_purchase_sn='.$sn;
    return $this->db->getOne($sql)?$this->orderSn():$sn;
}
//根据订单号查订单信息
public function lookpurchase($purchase_sn){
    $sql = 'select ecs_temp_purchase.money,ecs_temp_purchase.suppliers_id,ecs_temp_purchase.buyers_id,ecs_temp_buyers.temp_buyers_mobile,ecs_temp_purchase.state,ecs_temp_purchase.temp_purchase_id,ecs_temp_purchase.purchase_title from ecs_temp_purchase left join ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_purchase.suppliers_id where ecs_temp_purchase.temp_purchase_sn = \''.$purchase_sn.'\'';
   return $this->db->getRow($sql);
    

}
//判断来查看的是订单的报价人还是问价人
public function userid($order_id){
  $sql = 'select temp_purchase_id,buyers_id,suppliers_id from '.$this->table.' where quotation_id ='.$order_id;
  return $this->db->getRow($sql);
}
public function is_sn($sn){
   $sql = 'select count(*) from ' . $this->table  . ' where temp_purchase_sn='.$sn;
    return $this->db->getOne($sql);
}
}
?>