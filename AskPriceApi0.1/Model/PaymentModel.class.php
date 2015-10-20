<?php
defined('ACC')||exit('Acc Deined');
class PaymentModel extends Model {
protected $table = 'ecs_temp_payment';
    protected $pk = 'temp_payment_id';
    protected $fields = array('temp_payment_id','temp_purchase_sn','time','from_user','to_user','from_account','to_account','method','admin_id','user_id','money','client_from');

    protected $_valid = array(

                         
    );

    protected $_auto = array(

                            );

//买家确认收货，把账户缓存的钱转卖家账户余额里
public function tosuppliersaccount($money,$uid){
    $this->db->query("START TRANSACTION");
    $sql = 'update ecs_temp_account set withdraw = withdraw - '.$money.' where temp_buyers_id ='.$uid;
    $sql2 = 'update ecs_temp_account set total = total + '.$money.' where temp_buyers_id ='.$uid;
                
    $res = $this->db->query($sql);
    $rc = $this->db->affected_rows();
    $res2 = $this->db->query($sql2);
    $rc2 = $this->db->affected_rows();

    if($rc && $rc2){
    $this->db->query("COMMIT");
    $this->db->query("END"); 
    return true;
    }else{
    $this->db->query("ROLLBACK");
   $this->db->query("END"); 
    return false;
    }
}
//查此订单有没有在payment数据库插入过数据
public function selectpaymentall($sn){
  $sql = 'select * from ecs_temp_payment where temp_purchase_sn = \''.$sn.'\'';
  return $this->db->getRow($sql);

}

//在payment把原来的订单入账信息修改为一条退款信息，type=3，同时在卖家count里的缓存减去这笔钱，在买家的count账户的缓存加上这笔钱， 在acount插入一条数据,事务
 public function refund($suppliers_id,$buyers_id,$purchase_sn,$money){

 	              $this->db->query("START TRANSACTION");
                
                $sql = 'update ecs_temp_payment set type = 3 where user_id ='.$suppliers_id.' and temp_purchase_sn =\''.$purchase_sn.'\'';
                //从卖家账户减去这笔订单钱
                $sql2 = 'update ecs_temp_account set withdraw = withdraw - '.$money.' where temp_buyers_id ='.$rows['suppliers_id'];
                //给买家加上这笔定单钱
                $sql3 = 'update ecs_temp_account set withdraw = withdraw + '.$money.' where temp_buyers_id ='.$buyers_id;

                 $res = $this->db->query($sql);
                 $rc = $this->db->affected_rows();
                 $res2 = $this->db->query($sql2);
                 $rc2 = $this->db->affected_rows();
                 $res3 = $this->db->query($sql3);
                 $rc3 = $this->db->affected_rows();

                 if($rc && $rc2 && $rc3){
                    $this->db->query("COMMIT");
                    $this->db->query("END"); 
                    return true;
                    }else{
                    $this->db->query("ROLLBACK");
                    $this->db->query("END"); 

               					return false;
                 }

 }           
//收支明细

public function payment_details($page,$limit,$type,$uid){

    //统计页数
    //收入
        $sql1 = 'select count(*) as total from ecs_temp_payment left join ecs_temp_purchase on ecs_temp_purchase.temp_purchase_sn = ecs_temp_payment.temp_purchase_sn where ecs_temp_purchase.state = 4 and ecs_temp_payment.type = 0 and ecs_temp_payment.user_id = '.$uid;
    //支出
        $sql2 = 'select count(*) as total from ecs_temp_payment where temp_purchase_sn = -1 and user_id = '.$uid;

       

 
    if($type == 1){
         $total = $this->db->getOne($sql);          
    }else if($type == 2){
         $total = $total = $this->db->getOne($sql2);           

    }else{
		$total1 = $this->db->getOne($sql); 
		$total2 = $total = $this->db->getOne($sql2); 
        $total = $total1+$total2;

    }
     //总页数
    $totalpage = ceil($total/$limit);



    $offset = ($page-1)*$limit;
    if(($page<1)||($page>$totalpage)){
        $page = 1;
    }
    //取数据
    if($type == 1){//收入

       $sql = 'select ecs_temp_payment.* from ecs_temp_payment left join ecs_temp_purchase on ecs_temp_purchase.temp_purchase_sn = ecs_temp_payment.temp_purchase_sn where ecs_temp_purchase.state = 4 and ecs_temp_payment.type = 0 and ecs_temp_payment.user_id = '.$uid .' order by ecs_temp_payment.time desc limit '.$offset.','.$limit;    


    }else if($type == 2){//支出
  
        $sql = 'select * from ecs_temp_payment where temp_purchase_sn = -1 and user_id = '.$uid.' order by time desc limit '.$offset.','.$limit;
    }else{//全部
     
    			$sql = 'select ecs_temp_payment.* from ecs_temp_payment left join ecs_temp_purchase on ecs_temp_purchase.temp_purchase_sn = ecs_temp_payment.temp_purchase_sn where ecs_temp_purchase.state = 4 and ecs_temp_payment.type = 0 and ecs_temp_payment.user_id = '.$uid.' union all select * from ecs_temp_payment where temp_purchase_sn = -1 and user_id = '.$uid.' order by time desc limit '.$offset.','.$limit;
    }
    $rs = $this->db->query($sql);
    $arr = array();
    while($row = mysql_fetch_assoc($rs)){
       
      
        $info['pay_type'] = $row['temp_purchase_sn'] == -1 ? '1' : '2';//1 提现， 2 在线支付
        $info['time'] = $row['time'];
        $info['money'] = $row['money'];

        $arr [] = $info;
    }
  
   return $arr;



}



















}
?>