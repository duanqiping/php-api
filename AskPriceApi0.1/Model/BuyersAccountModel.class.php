<?php
defined('ACC')||exit('Acc Deined');
class BuyersAccountModel extends Model {
protected $table = 'ecs_temp_buyers_account';
    protected $pk = 'temp_buyers_account_id';
    protected $fields = array('temp_buyers_id','type','person','city','account','bank','alipay');

    protected $_valid = array(

                            array('alipay',1,'账号必须存在','4800','require'), //代表0支付宝
                            array('person',1,'开户名必须存在','4800','reuqire')
    );

    protected $_auto = array(

                            array('type','value',0),//0支付宝1其他银行
                            array('city','value',''),
                            array('account','value',''),
                            array('bank','value','')

                            );

//判断用户是否有账户
    public function is_buyersaccount($uid){
    	   $sql = 'select count(*) from ecs_temp_buyers_account where temp_buyers_id ='.uid;
    	   return $this->db->getOne($sql);
    }

//查看自己的账户信息
    public function selectmyaccount($uid){
         $sql = 'select temp_buyers_id,person,alipay from ecs_temp_buyers_account where temp_buyers_id ='.$uid;
         return $this->db->getRow($sql);

    }
//根据用户ID修改用户信息
    public function updatemyaccount($data,$uid){
        $rs = $this->db->autoExecute($this->table,$data,'update',' where temp_buyers_id ='.$uid);
        if($rs) {
            return $this->db->affected_rows();
        } else {
            return false;
        }

    }





















}
?>
