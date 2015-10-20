<?php
//添加地址
defined('ACC')||exit('Acc Deined');


class AddressModel extends Model {
    protected $table = 'ecs_temp_buyers_address';
    protected $pk = 'temp_buyers_address_id';
    protected $fields = array('temp_buyers_address_id','temp_buyers_id','name','address','phone','mobile','email','defaultaddress');

    protected $_valid = array(
                            array('mame',0,'用户名不能为空','4800','require'),
                            array('address',0,'地址不能为空','4800','require'),
                            array('mobile',0,'手机号码格式不正确','4800','mobile')                       
                         ); 


    public function select($uid) {
        $sql = 'select temp_buyers_address_id,name,address,mobile,defaultaddress from ' . $this->table . ' where temp_buyers_id =' . $uid;
        return $this->db->getAll($sql);
    }
    //查同一个人几个地址信息
    public function count($uid){
        $sql = 'select count(*) from ' . $this->table . ' where temp_buyers_id =' . $uid;
        return $this->db->getOne($sql);
    }
    //修改默认地址
    public function update($data,$id) {

        $rs = $this->db->autoExecute($this->table,$data,'update',' where temp_buyers_id ='.$id);
        if($rs) {
            return $this->db->affected_rows();
        } else {
            return false;
        }
    }
//修改地址
    public function updateaddress($data,$uid,$id) {

        $rs = $this->db->autoExecute($this->table,$data,'update',' where temp_buyers_id ='.$uid.' and temp_buyers_address_id ='.$id);
        if($rs) {
            return $this->db->affected_rows();
        } else {
            return false;
        }
    }
    //默认地址信息
      public function defaultaddress($uid) {

        $sql = 'select temp_buyers_address_id,name,address,mobile,defaultaddress from ' . $this->table . ' where defaultaddress = 1 and temp_buyers_id =' . $uid;
       
        return $this->db->getRow($sql);
    }  
//判断修改的是不是和原来的一样
    public function checksame($data,$aid){

        $sql = "select ".implode(',',array_keys($data))." from " . $this->table . ' where ' . $this->pk . '=' . $aid;

        $row = $this->db->getRow($sql);

        if(empty($row)) {

                return false;
            }
        $c = array_diff($data, $row);
        if(empty($c)){
           return true;
        }else{
           return false;
        }

       } 
}
?>