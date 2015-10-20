<?php
defined('ACC')||exit('Acc Deined');
class AccountModel extends Model {
protected $table = 'ecs_temp_account';
    protected $pk = 'temp_account_id';
    protected $fields = array('temp_buyers_id','total','withdraw','cashout');

    protected $_valid = array(

                         
    );

    protected $_auto = array(

                            );
//是否有账户在acount
    public function is_account($id){
    	  $sql = 'select count(*) from ecs_temp_account where temp_buyers_id ='.$id;
       return $this->db->getOne($sql);

    }
//查看自己的账户余额
    public function showmoney($uid){
    	    $sql = 'select total from ecs_temp_account where temp_buyers_id = '.$uid.'limit 1';
    	    return $this->db->getOne($sql);


    }

//申请提现
public function applycash($mobile,$card,$uid,$money){
    //开启事务，插入数据，同时扣掉余额账户的钱
    $this->db->query("START TRANSACTION");
    $sql = 'insert into ecs_temp_payment (temp_purchase_sn,time,from_user,to_user,from_account,to_account,method,type,user_id,money) values (-1,\''.time().'\',\''.$mobile.'\',\'品材网支付\',\''.$card.'\',\'hbz@pcw268.com\',0,1,\''.$uid.'\',\''.$money.'\')';
    //echo $sql;
    $sql2 = 'update ecs_temp_account set total = total - '.$money.' where temp_buyers_id ='.$uid;
    $sql3 = 'update ecs_temp_account set cashout = cashout + '.$money.' where temp_buyers_id ='.$uid;
    $res = $this->db->query($sql);
    $rc = $this->db->insert_id();
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
}
?>