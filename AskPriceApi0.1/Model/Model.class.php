<?php
defined('ACC')||exit('ACC Denied');
class Model {
    protected $table = NULL; // 是model所控制的表
    protected $db = NULL; // 是引入的mysql对象

    protected $pk = '';
    protected $fields = array();
    protected $_auto = array();
    protected $_valid = array();
    protected $error = array();
    protected $errorcode = array();

    public function __construct() {
        $this->db = mysql::getIns();
    }

    public function table($table) {
        $this->table = $table;
    }


    /*
        自动过滤:
        负责把传来的数组
        清除掉不用的单元
        留下与表的字段对应的单元
        思路:
        循环数组,分别判断其key,是否是表的字段
        自然,要先有表的字段.

        表的字段可以desc表名来分析
        也可以手动写好 
        以tp为例,两者都行.

        先手动写
    */
    public function _facade($array=array()) {

        $data = array();
        foreach($array as $k=>$v) {
            if(in_array($k,$this->fields)) {  // 判断$k是否是表的字段
                $data[$k] = $v;
            }
        }

        return $data;
    }


    /*
    自动填充
    负责把表中需要值,而$_POST又没传的字段,赋上值
    比如 $_POST里没有add_time,即商品时间,
    则自动把time()的返回值赋过来
    */
    public function _autoFill($data) {
        foreach($this->_auto as $k=>$v) {
            if(!array_key_exists($v[0],$data)) {
                switch($v[1]) {
                    case 'value':
                    $data[$v[0]] = $v[2];
                    break;

                    case 'function':
                    $data[$v[0]] = call_user_func($v[2]);
                    break;
                    case 'callback':
                    $data[$v[0]] = call_user_func_array($v[2],array($v[4],$v[5]));
                    break;
                }
            }
        }

        return $data;
    }

    
    /*
        格式 $this->_valid = array(
                    array('验证的字段名',0/1/2(验证场景),'报错提示','require/in(某几种情况)/between(范围)/length(某个范围)','参数')
        );

        array('goods_name',1,'必须有商品名','requird'),
        array('cat_id',1,'栏目id必须是整型值','number'),
        array('is_new',0,'in_new只能是0或1','in','0,1')
        array('goods_breif',2,'商品简介就在10到100字符','length','10,100')

    */
    public function _validate($data) {

        if(empty($this->_valid)) {

            return true;
        }
        
        $this->error = array();

        foreach($this->_valid as $k=>$v) {
            switch($v[1]) {
                case 1://必须检验
                  
                    if(!isset($data[$v[0]])) {
                        
                        $this->error[] = $v[2];
                        $this->errorcode[]= $v[3];
                        return false;
                    }
                    
                    if(!isset($v[5])) {
                        $v[5] = '';
                    }

                    if(!$this->check($data[$v[0]],$v[4],$v[5])) {
                        $this->error[] = $v[2];
                        $this->errorcode[]= $v[3];
                        return false;
                    }
                    break;
                case 0://如果存在就检验
                    if(!isset($v[5])) {
                        $v[5] = '';
                    }
                    if(isset($data[$v[0]])) {
                        if(!$this->check($data[$v[0]],$v[4],$v[5])) {
                            $this->error[] = $v[2];
                            $this->errorcode[]= $v[3];
                            return false;
                        }
                    }
                    break;
                case 3://如果存在就检验
                    if(!isset($data[$v[0]])) {

                        $this->error[] = $v[2];
                        $this->errorcode[]= $v[3];
                        return false;
                    }
                    if(!$this->check($data[$v[0]],$v[4],$data[$v[5]])) {
                        $this->error[] = $v[2];
                        $this->errorcode[]= $v[3];
                        return false;
                    }
                    break;
                case 2://存在并且不为空就检验
                    if(isset($data[$v[0]]) && !empty($data[$v[0]])) {
                        if(!$this->check($data[$v[0]],$v[4],$v[5])) {
                            $this->error[] = $v[2];
                            $this->errorcode[]= $v[3];
                            return false;
                        }
                    }
            }
        }

        return true;

    }

    public function getErr(){
        return $this->error;
    }
    public function getErrCode(){
        return $this->errorcode;
    }

    public function check($value,$rule='',$parm='') {
        switch($rule) {
            case 'require':
                
                return !empty($value);

            case 'number':

                return is_numeric($value);

            case 'in':
                //var_dump($value);
                $tmp = explode(',',$parm);
                return in_array($value,$tmp);
            case 'between':
                list($min,$max) = explode(',',$parm);
                return $value >= $min && $value <= $max;
            case 'gt':
                  return $value>$parm;
            case 'length':
                list($min,$max) = explode(',',$parm);
                return strlen($value) >= $min && strlen($value) <= $max;
            case 'email':
                // 判断$value是否是email,可以用正则表达式,但现在没学.
                // 因此,此处用系统函数来判断
                return (filter_var($value,FILTER_VALIDATE_EMAIL) !== false);
            case 'mobile':     
                return  preg_match('/^1[358]\d{9}$/', $value);
            case 'verify':
                return $value == $_SESSION['checkcode'];
            case 'confirm':
               
                return $value === $parm;
            default:
                return false;
        }
    }

    /*
     在model父类里,写最基本的增删改查操作
    */

    /*
        parm array $data
        return bool
    */
    public function add($data) {

        return $this->db->autoExecute($this->table,$data);
    }

    /*
        parm int $id 主键
        return int 影响的行数
    */
    public function delete($id) {
        $sql = 'delete from ' .$this->table . ' where ' . $this->pk . '=' .$id;
        if($this->db->query($sql)) {
            return $this->db->affected_rows();
        } else {
            return false;
        }
    }


    /*
        parm array $data
        parm int $id
        return int 影响行数
    */
    public function update($data,$id) {

        $rs = $this->db->autoExecute($this->table,$data,'update',' where '.$this->pk.'='.$id);
        
        if($rs) {
            return $this->db->affected_rows();
        } else {
            return false;
        }
    }


    /*
        return Array
    */
    public function select() {
        $sql = 'select * from ' . $this->table;
        return $this->db->getAll($sql);
    }


    /*
        parm int $id
        return Array
    */

    public function find($id) {
        $sql = 'select * from ' .  $this->table . ' where ' . $this->pk . '=' . $id;
        return $this->db->getRow($sql);
    }

    public function insert_id() {
        return $this->db->insert_id();
    }
         /*
        获取每个商品对应的附件

    */

    public function getRequestGoodsattch($items) {
      // print_r($items);

        foreach($items as $k=>$v) {  
        //去商品表查附件
            $goodsarray = $v['goods'];

            
            foreach( $goodsarray as $key=>$value){
                $sql = 'select requestattach_id,typeid,file_url,img_thumb,icon_url';
                $sql .= ' from ecs_temp_requestattach where requestgoods_id ='.$value['requestgoods_id'];
               
                $row = $this->db->getAll($sql);//shang pin fu jian shuzu 
               //print_r($row);
                foreach($row as $a=>$b){
                    $row[$a]['file_url'] = NROOT.'/Guest/'.$b['file_url'];
                    $row[$a]['img_thumb'] = NROOT.'/Guest/'.$b['img_thumb'];
                    $row[$a]['icon_url'] = NROOT.'/Guest/'.$b['icon_url'];
                }

                $items[$k]['goods'][$key]["attaches"] = $row;
             }   

        }

        return $items;
       
    }

//查看某个询价是否有报价单$id询价单ID
    public function is_quotation($id,$suppliers_id=0) {
        if($suppliers_id){
            $sql = 'select count(*) from ecs_temp_quotation where suppliers_id = '.$suppliers_id.' and request_id = '.$id; 
        }else{
            $sql = 'select count(*) from ecs_temp_quotation where request_id = '.$id;  
        }


        $count= $this->db->getOne($sql);


        return $count;
       
    } 
//判断修改的是不是和原来的一样
    public function checksame($data,$mobile){

        $sql = "select ".implode(',',array_keys($data))." from " . $this->table . " where temp_buyers_mobile= '" . $mobile . "'";

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
//判断登录状态
    public function is_login(){
        if(!(isset($_SESSION['temp_buyers_id'])&&$_SESSION['temp_buyers_id']>0)){
            $response = array("success"=>"false","error"=>array("msg"=>'你还没有登录','code'=>4120));
            $response = ch_json_encode($response);
            exit($response);

        }
    }
}

    