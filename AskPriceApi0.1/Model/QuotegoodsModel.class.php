<?php
defined('ACC')||exit('Acc Deined');


class QuotegoodsModel extends Model {
    protected $table = 'ecs_temp_quotegoods';
    protected $pk = 'quotegoods_id';
    protected $fields = array('quotegoods_id','quotation_id','suppliers_id','requestgoods_id','goods_price','is_attach','comet');

    protected $_valid = array(
                            array('requestgoods_id',1,'requestgoods_id为整数','4800','number'),
                            array('is_attach',1,'is_attach只能是0或1','4800','in','0,1'),
                            array('goods_price',1,'商品单价不能为空','4800','require'),
                            array('goods_price',1,'商品单价为数字','4800','require'),
                            array('comet',0,'备注不得超过1000字','4800','length','1,1000')
                     );





}


