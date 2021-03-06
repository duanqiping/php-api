<?php
/*
file init.php
作用:框架初始化
*/


// 初始化当前的绝对路径
// 换成正斜线是因为 win/linux都支持正斜线,而linux不支持反斜线

defined('ACC')||exit('ACC Denied');


define('ROOT',str_replace('\\','/',dirname(dirname(__FILE__))) . '/');
define('MROOT',dirname(ROOT).'/');

define('NROOT','http://192.168.1.61/ecshop2');
define('DEBUG',true);


require(ROOT . 'include/lib_base.php');

function __autoload($class) {
    if(strtolower(substr($class,-5)) == 'model') {
        require(ROOT . 'Model/' . $class . '.class.php');
    } else if(strtolower(substr($class,-4)) == 'tool') {
        require(ROOT . 'tool/' . $class . '.class.php');
    } else {
        require(ROOT . 'include/' . $class . '.class.php');
    }
}



// 过滤参数,用递归的方式过滤$_GET,$_POST,$_COOKIE,暂时不会
 if(!get_magic_quotes_gpc()){
				$_GET = _addslashes($_GET);
				$_POST = _addslashes($_POST);
				$_COOKIE = _addslashes($_COOKIE);
 }
 //手动设置 Session 的生存期一天
$lifeTime = 24 * 3600;
session_set_cookie_params($lifeTime);

// 开启session
session_start();


// 设置报错级别


if(defined('DEBUG')) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}
