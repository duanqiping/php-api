<?php
//询价单表
defined('ACC')||exit('Acc Deined');


class SubinquiryModel extends Model {
    protected $table = 'ecs_temp_request';
    protected $pk = 'request_id';
    protected $fields = array('request_id','sn','temp_buyers_id','title','name','address','mobile','is_attach','recieve_time','addtime','is_check','adminid','admin_edit_time','type','state','temp_buyers_address_id','comet','suppliers_id');

    protected $_valid = array(
                            array('type',0,'type只能是0或1或2','4800','in','0,1,2'),
                            array('suppliers_id',0,'suppliers_id必须是数字','4800','number'),
                            array('name',1,'收货人不能为空','4800','require'),
                            array('mobile',1,'联系方式不能为空','4800','require'),
                            array('recieve_time',1,'收货时间不能为空','4800','require'),
                            array('address',1,'收货地址不能为空','4800','require'),
                            array('temp_buyers_address_id',1,'temp_buyers_address_id必须为整数','4800','number'),
                            array('comet',0,'备注不得超过1000字','4800','length','0,1000')
                            
                         ); 

    public function invoke($request_id) {
        $this->delete($request_id); // 先删掉询价单
        $sql = 'delete from ecs_temp_requestgoods where request_id = ' . $request_id; // 再删询价单对应的商品

        $sql1 = 'delete from ecs_temp_requestattach where request_id = ' . $request_id; // 再删询价单对应的商品的附件

        //再删除文件
        $sql3 = 'select file_url,img_thumb from ecs_temp_requestattach where request_id = ' . $request_id;
        $list = $this->db->getAll($sql3);
        foreach($list as $v){
            $file_url = MROOT.'Guest/'.$v['file_url'];
            $img_thumb = MROOT.'Guest/'.$v['img_thumb'];
            unlink($file_url);
            unlink($img_thumb);

        }

        if($this->db->query($sql) && $this->db->query($sql1)){

                return 1;
        }else{
                return 0;
        }
        
    }
//删除询价单商品和附件
    public function delgoods($request_id) {
        $sql = 'delete from ecs_temp_requestgoods where request_id = ' . $request_id; // 再删询价单对应的商品

        $sql1 = 'delete from ecs_temp_requestattach where request_id = ' . $request_id; // 再删询价单对应的商品的附件
        //再删除文件
        $sql3 = 'select file_url,img_thumb from ecs_temp_requestattach where request_id = ' . $request_id;
        $list = $this->db->getAll($sql3);
        foreach($list as $v){
            $file_url = MROOT.'Guest/'.$v['file_url'];
            $img_thumb = MROOT.'Guest/'.$v['img_thumb'];
            unlink($file_url);
            unlink($img_thumb);

        }

        if($this->db->query($sql) && $this->db->query($sql1)){

                return 1;
        }else{
                return 0;
        }
        
    }  
    //取出所有的公开询价单
    public function requestlist($page,$limit,$uid=0){

    //统计页数
    if($uid){
        //我的询价单总数
        $sql = 'select count(*) from ecs_temp_request where type !=2 and state in (0,1) and temp_buyers_id = '.$uid;
    }else{
        //公开询价单总数
            $sql = 'select count(*) from ecs_temp_request where is_check = 1 and type = 0 and state = 0 ';

    }

    //总条数

    $total = $this->db->getOne($sql);

    //总页数
    $totalpage = ceil($total/$limit);

    //偏移量
    $offset = ($page-1)*$limit;

    if($page>$totalpage){
        $page = 1;
    }

        $sql = 'select ecs_temp_request.request_id,ecs_temp_request.sn,ecs_temp_request.title,ecs_temp_request.name,ecs_temp_request.address,ecs_temp_request.mobile,ecs_temp_request.recieve_time,ecs_temp_request.temp_buyers_address_id,ecs_temp_request.addtime,ecs_temp_request.is_check,ecs_temp_request.state,ecs_temp_request.comet,';

        $sql .= 'ecs_temp_buyers.temp_buyers_id,ecs_temp_buyers.temp_buyers_mobile,ecs_temp_buyers.nick,ecs_temp_buyers.photo,ecs_temp_buyers.info ';
    
        $sql .= 'from ecs_temp_request left join  ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_request.temp_buyers_id';
        if($uid){
            $sql .= ' where ecs_temp_request.type !=2 and ecs_temp_request.state in (0,1) and ecs_temp_request.temp_buyers_id = '.$uid.' order by ecs_temp_request.addtime desc limit ' . $offset . ',' . $limit;
        }else{
            $sql .= ' where ecs_temp_request.is_check = 1 and ecs_temp_request.type = 0 and ecs_temp_request.state = 0 order by ecs_temp_request.addtime desc limit ' . $offset . ',' . $limit;
        }

        $rs = $this->db->query($sql);
        
        $infos = array();
        //$goodsinfo = array();
        //$atts = array();
        while($row = mysql_fetch_assoc($rs)){
  
                $info['request_id'] = $row['request_id'];
                $info['sn'] = $row['sn'];
                $info['addressinfo']['temp_buyers_address_id'] = $row['temp_buyers_address_id'];
                $info['addressinfo']['name'] = $row['name'];
                $info['addressinfo']['address'] = $row['address'];
                $info['addressinfo']['mobile'] = $row['mobile'];
                $info['recieve_time'] = $row['recieve_time'];
                $info['is_check'] = $row['is_check'];
                $info['state'] = $row['state'];
                $info['addtime'] = $row['addtime'];
                $info['comet'] = $row['comet'];
                $info['buyers']['temp_buyers_id'] = $row['temp_buyers_id'];
                $info['buyers']['temp_buyers_mobile'] = $row['temp_buyers_mobile'];
                $info['buyers']['nick'] = $row['nick'];
                if($row['photo']){
                    $info['buyers']['photo'] = NROOT.'/Guest/'.$row['photo'];
                }else{
                    $info['buyers']['photo'] = '';
                }
                
                $info['buyers']['info'] = $row['info'];


                $infos[] = $info;
               

        }
 
        return $infos;
    }
     /*
        获取每个询价单的商品详细信息

    */

    public function getRequestGoods($items) {

        foreach($items as $k=>$v) {  
        //去商品表查商品
        $sql = 'select request_id,requestgoods_id,goods_name,goods_version,goods_account,goods_unit,comet,is_attach';
        $sql .= ' from ecs_temp_requestgoods where request_id ='.$v['request_id'];
        $row = $this->db->getAll($sql);
         $items[$k]['goods'] = $row;
        }


        return $items;
       
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
    /*
        获取每个询价单的报价单数量
        params array $items 报价单数组
        return 每个询价单的报价单数量
    */

    public function getForRequest($items) {

        foreach($items as $k=>$v) {  
        $sql = 'select count(*) as quotationer from (select request_id from ecs_temp_quotation where state = 0 and request_id = '.$v['request_id'].'  group by suppliers_id) as temp';

            $row = $this->db->getRow($sql);

            $items[$k]['quotationer'] = $row['quotationer'];
        
        }

        return $items;
       
    }
   
    //根据 ID 查一条询价详细信息不管是公开还是私密
    public function inquiryinfo($id){
        $sql = 'select ecs_temp_request.request_id,ecs_temp_request.sn,ecs_temp_request.title,ecs_temp_request.name,ecs_temp_request.address,ecs_temp_request.mobile,ecs_temp_request.recieve_time,ecs_temp_request.temp_buyers_address_id,ecs_temp_request.addtime,ecs_temp_request.is_check,ecs_temp_request.state,ecs_temp_request.comet,';

        $sql .= 'ecs_temp_buyers.temp_buyers_id,ecs_temp_buyers.temp_buyers_mobile,ecs_temp_buyers.nick,ecs_temp_buyers.photo,ecs_temp_buyers.info ';
    
        $sql .= 'from ecs_temp_request left join  ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_request.temp_buyers_id ';
        $sql .= 'where ecs_temp_request.state in (0,1) and ecs_temp_request.request_id ='.$id;
        $rs = $this->db->query($sql);
        
        $infos = array();
        //$goodsinfo = array();
        //$atts = array();
        while($row = mysql_fetch_assoc($rs)){
  
                $info['request_id'] = $row['request_id'];
                $info['sn'] = $row['sn'];
                $info['addressinfo']['temp_buyers_address_id'] = $row['temp_buyers_address_id'];
                $info['addressinfo']['name'] = $row['name'];
                $info['addressinfo']['address'] = $row['address'];
                $info['addressinfo']['mobile'] = $row['mobile'];
                $info['recieve_time'] = $row['recieve_time'];
                $info['is_check'] = $row['is_check'];
                $info['state'] = $row['state'];
                $info['addtime'] = $row['addtime'];
                $info['comet'] = $row['comet'];
                $info['buyers']['temp_buyers_id'] = $row['temp_buyers_id'];
                $info['buyers']['temp_buyers_mobile'] = $row['temp_buyers_mobile'];
                $info['buyers']['nick'] = $row['nick'];
                if($row['photo']){
                    $info['buyers']['photo'] = NROOT.'/Guest/'.$row['photo'];
                }else{
                    $info['buyers']['photo'] = '';
                }
                
                $info['buyers']['info'] = $row['info'];


                $infos[] = $info;
               

        }

        return $infos;
    }
 //我有没有对这个问价报过价格
 public function myisquotationtoask($items,$suppliers_id){
        foreach($items as $k=>$v) {  
        $sql = 'select quotation_id from ecs_temp_quotation where request_id = '.$v['request_id'].'  and state = 0 and suppliers_id = '.$suppliers_id;
    
        $row = $this->db->getRow($sql);
        if(empty($row)){
            $items[$k]['is_quotation'] = 0;
        }else{
        $items[$k]['quotation_id'] = $row['quotation_id'];
        $items[$k]['is_quotation'] = 1;

        }

        
        }

        return $items;

 }
  
}
?>