<?php
//报价单表详情
defined('ACC')||exit('Acc Deined');

class QuotationModel extends Model {
    protected $table = 'ecs_temp_quotation';
    protected $pk = 'quotation_id';
    protected $fields = array('quotation_id','sn','request_id','suppliers_id','buyers_id','addtime','is_attach','flow_price','total_price','state','comet','is_read');

    protected $_valid = array(
                            
                            array('request_id',0,'request_id为整数','4800','number'),
                            array('buyers_id',0,'buyers_id为整数','4800','number'),
                            array('buyers_id',0,'buyers_id大于0','4800','gt','0'),
                            array('flow_price',1,'物流费为数字','4800','number'),

                
                            array('total_price',1,'total_price必须数字','4800','number'),
   
                            array('comet',0,'备注不得超过1000字','4800','length','0,1000')
                            
                         ); 

    protected $_auto = array(                     
                            array('state','value','0'),
                            array('is_attach','value','0')
                            );
    public function invoke($quotation_id) {
        $this->delete($quotation_id); // 先删掉报价单
        $sql = 'delete from ecs_temp_quotegoods where quotation_id = ' .$quotation_id; // 再删询价单对应的商品

        if($this->db->query($sql)){

                return 1;
        }else{
                return 0;
        }
        
    }
 
    //取出我的报价单列表
    public function quotationlist($page,$limit,$uid){

    //统计页数

        //我的报价单总数
        $sql = 'select count(*) from ecs_temp_quotation where suppliers_id = '.$uid;


    //总条数

    $total = $this->db->getOne($sql);

    //总页数
    $totalpage = ceil($total/$limit);

    //偏移量
    $offset = ($page-1)*$limit;

    if($page>$totalpage){
        $page = 1;
    }

        $sql = 'select ecs_temp_request.request_id,ecs_temp_request.name,ecs_temp_request.address,ecs_temp_request.mobile,ecs_temp_request.recieve_time,ecs_temp_request.temp_buyers_address_id,ecs_temp_request.comet,';
        $sql .= 'ecs_temp_quotation.quotation_id,ecs_temp_quotation.sn,ecs_temp_quotation.addtime,ecs_temp_quotation.flow_price,ecs_temp_quotation.total_price,ecs_temp_quotation.state,';
        $sql .= 'ecs_temp_buyers.temp_buyers_id,ecs_temp_buyers.temp_buyers_mobile,ecs_temp_buyers.nick,ecs_temp_buyers.photo,ecs_temp_buyers.info ';
    
        $sql .= 'from ecs_temp_quotation left join ecs_temp_request on ecs_temp_quotation.request_id = ecs_temp_request.request_id left join  ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_quotation.buyers_id';

        $sql .= ' where ecs_temp_quotation.suppliers_id = '.$uid.' order by  ecs_temp_quotation.addtime desc limit ' . $offset . ',' . $limit;
        

        $rs = $this->db->query($sql);
        
        $infos = array();
        //$goodsinfo = array();
        //$atts = array();
        while($row = mysql_fetch_assoc($rs)){
                $info['quotation_id'] = $row['quotation_id'];
                $info['request_id'] = $row['request_id'];
                $info['sn'] = $row['sn'];
                $info['flow_price'] = $row['flow_price'];
                $info['total_price'] = $row['total_price'];
                $info['addressinfo']['temp_buyers_address_id'] = $row['temp_buyers_address_id'];
                $info['addressinfo']['name'] = $row['name'];
                $info['addressinfo']['address'] = $row['address'];
                $info['addressinfo']['mobile'] = $row['mobile'];
                $info['recieve_time'] = $row['recieve_time'];
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
        获取每个报价单的商品详细信息

    */

    public function getQuotationGoods($items) {

        foreach($items as $k=>$v) {  
        //去商品表查商品
        $sql = 'select ecs_temp_quotegoods.quotegoods_id,ecs_temp_quotegoods.goods_price,ecs_temp_quotegoods.requestgoods_id,';
        $sql .= 'ecs_temp_requestgoods.request_id,ecs_temp_requestgoods.goods_name,ecs_temp_requestgoods.goods_version,ecs_temp_requestgoods.goods_account,ecs_temp_requestgoods.goods_unit,ecs_temp_requestgoods.comet,ecs_temp_requestgoods.is_attach';
        $sql .= ' from ecs_temp_quotegoods left join ecs_temp_requestgoods on ecs_temp_requestgoods.requestgoods_id = ecs_temp_quotegoods.requestgoods_id';
        $sql .= ' where ecs_temp_quotegoods.quotation_id ='.$v['quotation_id'];

        $row = $this->db->getAll($sql);
         $items[$k]['goods'] = $row;
        }



        return $items;
         
    }
    //获取商品的价格
    public function getprice($items){
        $goods = $items[0]['goods'];
        foreach($goods as $k=>$v){
            $sql = 'select goods_price from ecs_temp_quotegoods where quotation_id = '.$items[0]['quotation_id'].' and requestgoods_id ='.$v['requestgoods_id'];
        
            $row = $this->db->getRow($sql);
            if(!empty($row)){
                $goods[$k]['goods_price'] = $row['goods_price'];
                $goods[$k]['is_checked'] = 1;
            }else{
                $goods[$k]['is_checked'] = 0;
            }
            
        }
        $items[0]['goods'] = $goods;
        return $items;
    }
    /*
        获单个报价单的商品详细信息

    */

    public function getUpdateQuotationGoods($items,$uid) {
        //print_r($items);
        foreach($items as $k=>$v) {  
        //去商品表查商品
        $sql = 'select ecs_temp_quotegoods.quotegoods_id,ecs_temp_quotegoods.goods_price,';
        $sql .= 'ecs_temp_requestgoods.requestgoods_id,ecs_temp_requestgoods.request_id,ecs_temp_requestgoods.goods_name,ecs_temp_requestgoods.goods_version,ecs_temp_requestgoods.goods_account,ecs_temp_requestgoods.goods_unit,ecs_temp_requestgoods.comet,ecs_temp_requestgoods.is_attach';
        $sql .= ' from ecs_temp_requestgoods left join ecs_temp_quotegoods on ecs_temp_requestgoods.requestgoods_id = ecs_temp_quotegoods.requestgoods_id';
        $sql .= ' where ecs_temp_quotegoods.suppliers_id = '.$uid.' and ecs_temp_requestgoods.request_id ='.$v['request_id'];
        //echo $sql;
        $row = $this->db->getAll($sql);

        foreach($row as $key=>$value){
            if(is_null($value['quotegoods_id'])){
                $row[$key]['is_checked'] = 0;
            }else{
                $row[$key]['is_checked'] = 1;
            }

        }

         $items[$k]['goods'] = $row;
        }



        return $items;
       
    }
     /*我的问价，别人对我的问价的报价列表 对一个问价，同一个人只有一个报价
    $request_id 问价单id
    $uid 买家id
    */
    public function MyrequestToQuotationlist($request_id,$page=1,$limit=5){
        //统计页数

        //我的报价单总数
        $sql = 'select count(*) from ecs_temp_quotation where request_id  = '.$request_id;


    //总条数

    $total = $this->db->getOne($sql);

    //总页数
    $totalpage = ceil($total/$limit);

    //偏移量
    $offset = ($page-1)*$limit;

    if($page>$totalpage){
        $page = 1;
    }

        
        $sql = 'select ecs_temp_quotation.quotation_id,ecs_temp_quotation.sn,ecs_temp_quotation.addtime,ecs_temp_quotation.flow_price,ecs_temp_quotation.total_price,ecs_temp_quotation.state,';
        $sql .= 'ecs_temp_buyers.temp_buyers_id,ecs_temp_buyers.temp_buyers_mobile,ecs_temp_buyers.nick,ecs_temp_buyers.photo,ecs_temp_buyers.info ';
    
        $sql .= 'from ecs_temp_quotation left join  ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_quotation.suppliers_id';

        $sql .= ' where ecs_temp_quotation.state = 0  and ecs_temp_quotation.request_id  = '.$request_id.' order by  ecs_temp_quotation.addtime limit ' . $offset . ',' . $limit;
        

        $rs = $this->db->query($sql);
        
        $infos = array();
        //$goodsinfo = array();
        //$atts = array();
        while($row = mysql_fetch_assoc($rs)){
                $info['quotation_id'] = $row['quotation_id'];

                $info['sn'] = $row['sn'];
                $info['flow_price'] = $row['flow_price'];
                $info['total_price'] = $row['total_price'];
                $info['state'] = $row['state'];
                $info['addtime'] = $row['addtime'];

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

    //根据 ID 查一条报价单详细信息
    public function quotationinfo($id,$uid,$type='quotation'){
        $sql = 'select ecs_temp_request.request_id,ecs_temp_request.name,ecs_temp_request.address,ecs_temp_request.mobile,ecs_temp_request.recieve_time,ecs_temp_request.temp_buyers_address_id,ecs_temp_request.comet,ecs_temp_request.type,';
        $sql .= 'ecs_temp_quotation.quotation_id,ecs_temp_quotation.sn,ecs_temp_quotation.suppliers_id,ecs_temp_quotation.addtime,ecs_temp_quotation.flow_price,ecs_temp_quotation.total_price,ecs_temp_quotation.state,';
        $sql .= 'ecs_temp_buyers.temp_buyers_id,ecs_temp_buyers.temp_buyers_mobile,ecs_temp_buyers.nick,ecs_temp_buyers.photo,ecs_temp_buyers.info ';
    
        $sql .= 'from ecs_temp_quotation left join ecs_temp_request on ecs_temp_quotation.request_id = ecs_temp_request.request_id left join  ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_quotation.buyers_id';
        if($type == 'quotation'){
        $sql .= ' where ecs_temp_quotation.quotation_id = '.$id;
        }else if($type == 'request'){
        $sql .= ' where ecs_temp_quotation.state = 0 and ecs_temp_quotation.suppliers_id = '.$uid.' and ecs_temp_quotation.request_id = '.$id;        
        }
        

        $rs = $this->db->query($sql);
        
        $infos = array();
        //$goodsinfo = array();
        //$atts = array();
        while($row = mysql_fetch_assoc($rs)){
                $info['type'] = $row['type'];
                $info['quotation_id'] = $row['quotation_id'];
                $info['request_id'] = $row['request_id'];
                $info['suppliers_id'] = $row['suppliers_id'];
                $info['sn'] = $row['sn'];
                $info['flow_price'] = $row['flow_price'];
                $info['total_price'] = $row['total_price'];
                $info['addressinfo']['temp_buyers_address_id'] = $row['temp_buyers_address_id'];
                $info['addressinfo']['name'] = $row['name'];
                $info['addressinfo']['address'] = $row['address'];
                $info['addressinfo']['mobile'] = $row['mobile'];
                $info['recieve_time'] = $row['recieve_time'];
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
    //查看报价单的状态
    public function lookstate($id){

        $sql = 'select ecs_temp_quotation.state as qstate,ecs_temp_request.type as rstate,ecs_temp_request.request_id,ecs_temp_request.temp_buyers_id from ecs_temp_quotation left join ecs_temp_request on ecs_temp_request.request_id = ecs_temp_quotation.request_id where  ecs_temp_quotation.quotation_id = '.$id;
        return $this->db->getRow($sql);


    }

    //删除报价单商品
    public function delgoods($id) {
        $sql = 'delete from ecs_temp_quotegoods where quotation_id = ' . $id; 

        if($this->db->query($sql)){

                return 1;
        }else{
                return 0;
        }
        
    }  
//判断来查看的是订单的报价人还是问价人
public function userid($order_id,$uid){
  $sql = 'select quotation_id,buyers_id,suppliers_id from '.$this->table.' where (suppliers_id = '.$uid.' or buyers_id = '. $uid . ') and request_id ='.$order_id;
  return $this->db->getRow($sql);
}



 
} 
?>