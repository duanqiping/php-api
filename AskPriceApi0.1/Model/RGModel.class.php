<?php
defined('ACC')||exit('Acc Deined');


class RGModel extends Model {
    protected $table = 'ecs_temp_requestgoods';
    protected $pk = 'requestgoods_id';
    protected $fields = array('requestgoods_id','request_id','temp_buyers_id','goods_name','goods_version','goods_account','goods_unit','is_attach','comet');

    protected $_valid = array(
                            array('goods_name',0,'商品名不能为空','4800','require'),
                            array('goods_version',0,'商品型号不能为空','4800','require'),
                            array('goods_account',0,'商品数量不能为空','4800','require'),
                            array('goods_account',0,'商品数量必须是整型值','4800','number'),
                            array('goods_unit',0,'商品单位不能为空','4800','require'),
                            array('is_attach',0,'is_attach只能是0和1','4800','in','0,1')
                     );





}


