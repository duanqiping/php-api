<?php
defined('ACC')||exit('Acc Deined');


class UserModel extends Model {
    protected $table = 'ecs_temp_buyers';
    protected $pk = 'temp_buyers_id';
    protected $fields = array('temp_buyers_id','is_check','temp_buyers_mobile','temp_buyers_password','add_time','nick','client','lastlogin','info');

    protected $_valid = array(
                       // array('checkcode',1,'验证码错误','4109','verify'),
                        array('temp_buyers_mobile',0,'手机号码格式不正确','4800','mobile'),
                        array('temp_buyers_password',1,'密码不能为空','4800','require'),
                        array('temp_buyers_password',1,'密码长度至少为6位','4800','length','6,32')
                       // array('repassword','3','确认密码不正确','4114','confirm','temp_buyers_password') // 验证确认密码是否和密码一致
                       /* array('nick',0,'长度不能超过30个字符','4800','length','1,32'),
                        array('info',0,'长度不能超过300个字符','4800','length','1,300'),
                        array('photo',0,'长度不能超过100个字符','4800','length','1,100')
*/
    );

    protected $_auto = array(

                            array('is_check','value',1),
                            array('add_time','function','time'),
                            array('client','value','2'),
                            array('lastlogin','function','time')
                            );


    /*
        用户注册
    */
    public function reg($data) {
        if($this->checkUser($data['temp_buyers_mobile'])){
           return false;
        }
        if($data['temp_buyers_password']){
          $data['temp_buyers_password'] = $this->encPasswd($data['temp_buyers_password']);
        }
        return $this->add($data);
    }
    protected function encPasswd($p) {
        return md5($p);
    }
    //修改密码,昵称，介绍，头像
    public function updateinfo($data,$mobile){

        $rs = $this->db->autoExecute($this->table,$data,'update',' where temp_buyers_mobile='.$mobile);

        if($rs) {
            return $this->db->affected_rows();
        } else {
            return false;
        }

    }
    /*
    根据用户名查询用户信息
    */
    public function getuserinfo($username) {

            $sql = "select temp_buyers_id,temp_buyers_mobile,nick,photo,info from " . $this->table . " where temp_buyers_mobile= '" . $username . "'";
     
            $row = $this->db->getRow($sql);
            
            if(empty($row)) {

                return false;
            }

            return $row;
        }


    /*
    根据用户名查询用户信息
    */
    public function checkUser($username,$passwd='') {
        if($passwd == '') {
            $sql = 'select count(*) from ' . $this->table . " where temp_buyers_mobile='" .$username . "'";
            return $this->db->getOne($sql);  //因为继承了Model.class.php
        } else {
       
            $sql = "select temp_buyers_id,temp_buyers_mobile,nick,temp_buyers_password,photo,info from " . $this->table . " where temp_buyers_mobile= '" . $username . "'";
     
            $row = $this->db->getRow($sql);
            
            if(empty($row)) {

                return false;
            }

            if($row['temp_buyers_password'] != $passwd) {
      
                return false;
            }

            
     
            return $row;
        }
    }
  //获取所有用户的用户名
  public function getalluser(){
    $sql = 'select temp_buyers_mobile from'.$this->table;
    return $this->db->getAll($sql);
  }  
  //获取密码
public function getpsw($username){
 $sql = "select temp_buyers_id,temp_buyers_mobile,nick,temp_buyers_password,photo,info from " . $this->table . " where temp_buyers_mobile= '" . $username . "'";
return $this->db->getRow($sql);

}
//模糊查询用户信息
public function lookupinfo($mobile,$page,$limit){
    //统计页数

        //我的报价单总数
   $sql = "select count(*) from " . $this->table . " where temp_buyers_mobile like '%" . $mobile . "%'";


    //总条数

    $total = $this->db->getOne($sql);

    //总页数
    $totalpage = ceil($total/$limit);

    //偏移量
    $offset = ($page-1)*$limit;

    if($page>$totalpage){
        $page = 1;
    }
 $sql = "select temp_buyers_id,temp_buyers_mobile,nick,temp_buyers_password,photo,info from " . $this->table . " where temp_buyers_mobile like '%" . $mobile . "%' limit ".$offset . "," . $limit;
 return $this->db->getAll($sql);
}


}


?>