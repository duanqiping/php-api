<?php
defined('ACC')||exit('Acc Deined');


class UserOnLineModel extends Model {
    protected $table = 'ecs_temp_useronline';
    protected $pk = 'online_id';
    protected $fields = array('online_buyers_id','addr','active_time','online_id');


    protected $_auto = array(
                            array('addr','value',''),
                            array('active_time','function','time')
                            );


    /*
        添加登录信息
    */
    public function addlogin($data) {
       // print_r($data);
        $this->add($data);

    }
   public function count($id){
        $sql = 'select count(*) from ' . $this->table . " where online_buyers_id ='" .$id . "'";
        return $this->db->getOne($sql);

   }
    public function updatetime($data,$id){
            $rs = $this->db->autoExecute($this->table,$data,'update',' where online_buyers_id='.$id);
            if($rs) {
                return $this->db->affected_rows();
            } else {
                return false;
            }

        }
}


?>