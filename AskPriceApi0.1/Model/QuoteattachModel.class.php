<?php
defined('ACC')||exit('Acc Deined');


class QuoteattachModel extends Model {
    protected $table = 'ecs_temp_quoteattach';
    protected $pk = 'quoteattach_id';
    protected $fields = array('quoteattach_id','quotation_id','suppliers_id','type','file_url','img_thumb','icon_url','name');

}


