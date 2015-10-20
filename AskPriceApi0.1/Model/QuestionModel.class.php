<?php
defined('ACC')||exit('Acc Deined');


class QuestionModel extends Model {
    protected $table = 'ecs_temp_comment';
    protected $pk = 'id';
    protected $fields = array('id','mobile','title','content','add_time');

    protected $_valid = array(

                        array('content',1,'不少于15字符','4800','length','15,500')

    );

    protected $_auto = array(
                            array('add_time','function','time')

                            );


    /*
        用户反馈
    */
    public function rep($data) {
        return $this->add($data);
    }


}


?>