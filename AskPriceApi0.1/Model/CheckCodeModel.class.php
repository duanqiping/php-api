<?php
defined('ACC')||exit('Acc Deined');


class CheckCodeModel extends Model {
    protected $table = 'ecs_temp_checkcode';
    protected $pk = 'id';
    //ID mobile(手机号) checkCode(验证码) ip(IP地址) createAt(创建时间) expireAt(时效时间) isUse(是否使用) usingAt(使用时间)
    protected $fields = array('id','mobile','code','ip','createAt','expireAt','isUse','usingAt');

    protected $_valid = array(
                            array('type',1,'type必须存在','4800','require'),
                            array('mobile',1,'手机号必须存在','4800','require'),
                            array('mobile',1,'手机号格式不正确','4800','mobile'),
                            
    );
    
    protected $_auto = array(
                            array('ip','function','GetIP'),
                            array('createAt','function','time'),
                            array('expireAt','function','timeadd'),//5分钟后时效
                            array('isUse','value','0'),
                            array('usingAt','value','0')
                            );



    /*
        验证验证码
    */
    public function addcheck($data) {
        return $this->add($data);
    }
    

    /*
    根据手机号查询验证码
    */
    public function findOne($mobile,$now) {
       
            $sql = 'select code,createAt from ' . $this->table . " where expireAt > '".$now."' and mobile='" .$mobile . "' order by createAt desc limit 1";
        
            $row = $this->db->getRow($sql);

            if(empty($row)) {
                return false;
            }
            return $row;
        
    }
    //删除之前的短信
    public function delcode($mobile,$now){
        $sql = 'delete from ' .$this->table . " where expireAt < '".$now."' and mobile='" .$mobile;
        if($this->db->query($sql)) {
            return $this->db->affected_rows();
        } else {
            return false;
        }
    }
    //删除6条短信
        public function delcodesix($mobile){
        $sql = 'delete from ' .$this->table . " where mobile='" .$mobile . "' order by createAt desc limit 10";
        if($this->db->query($sql)) {
            return $this->db->affected_rows();
        } else {
            return false;
        }
    }
//设置验证码过期，把字段isUse修改为1，已经使用过
    public function setIsUsed($arr,$mobile,$code){ 
        return $this->db->autoExecute($this->table,$arr,$mode='update',$where = ' where code = \'' .$code . '\' and mobile=\'' .$mobile . '\'');
    }
    public function count($condition,$mode,$begin,$end){
        if($mode == 'mobile'){
        $sql = 'select count(*) from ' . $this->table . " where mobile='" .$condition . "' and createAt between '" .$begin . "' and '" .$end . "'";
         }else if($mode == 'ip'){
        $sql = 'select count(*) from ' . $this->table . " where ip='" .$condition . "' and createAt between '" .$begin . "' and '" .$end . "'";
         }
        return $this->db->getOne($sql);
    }
 }  
?>