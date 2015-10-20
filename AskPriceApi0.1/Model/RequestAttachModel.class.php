<?php
defined('ACC')||exit('Acc Deined');


class RequestAttachModel extends Model {
    protected $table = 'ecs_temp_requestattach';
    protected $pk = 'requestattach_id';
    protected $fields = array('requestattach_id','request_id','temp_buyers_id','requestgoods_id','typeid','file_url','icon_url','img_thumb');







}


