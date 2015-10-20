<?php
define('ACC',true);
require('./include/init.php');

session_destroy();

$response = array("success"=>"true","data"=>array("msg"=>'退出成功'));
$response = ch_json_encode($response);
exit($response);
?>