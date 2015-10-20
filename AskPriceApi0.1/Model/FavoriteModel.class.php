<?php
//收藏
defined('ACC')||exit('Acc Deined');


class FavoriteModel extends Model {
    protected $table = 'ecs_temp_favorite';
    protected $pk = 'fid';
    protected $fields = array('from_id','to_id','addtime');

    protected $_valid = array(
                       array('to_id',1,'to_id必须存在','4800','require')
    );

    protected $_auto = array(
                            array('addtime','function','time')
                            );

//我的收藏列表
public function myfavoritelist($uid){
    $sql = 'select ecs_temp_favorite.to_id,ecs_temp_buyers.temp_buyers_mobile,ecs_temp_buyers.nick,ecs_temp_buyers.photo,ecs_temp_buyers.info from ecs_temp_favorite left join ecs_temp_buyers on ecs_temp_buyers.temp_buyers_id = ecs_temp_favorite.to_id where ecs_temp_favorite.from_id ='.$uid.' order by ecs_temp_favorite.addtime desc';
   return  $this->db->getAll($sql);

}
//判断是否收藏过此好友
public function is_friend($uid,$toid){
    $sql ='select count(*) from '.$this->table.' where from_id ='.$uid.' and to_id ='.$toid;
    return $this->db->getOne($sql);

}







}
?>