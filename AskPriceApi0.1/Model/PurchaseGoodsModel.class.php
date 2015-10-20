<?php
defined('ACC')||exit('Acc Deined');
class PurchaseGoodsModel extends Model {
protected $table = 'ecs_temp_purchase_goods';
    protected $pk = 'temp_purchase_goods_id';
    protected $fields = array('temp_purchase_id','version','amount','unit','price','description','goods_id','name');

}
?>